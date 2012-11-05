<?php

/**
 * Api Put method to add/upload/insert stuff
 * on ClipBucket website
 */
include('../includes/config.inc.php');


$request = $_REQUEST;
$mode = $request['mode'];


$api_keys = $Cbucket->api_keys;
if($api_keys)
{
    if(!in_array($request['api_key'],$api_keys))
    {
        exit(json_encode(array('err'=>'App authentication error')));
    }
}

switch ($mode) {
    case "upload_video": {
            echo json_encode($_POST,$_FILES);
        }
        break;

    case "addComment": {
            $type = $request['type'];
            switch ($type) {
                case 'v':
                case 'video':
                default: {
                        $id = mysql_clean($request['obj_id']);
                        $comment = $request['comment'];
                        if ($comment == 'undefined')
                            $comment = '';
                        $reply_to = $request['reply_to'];

                        $cid = $cbvid->add_comment($comment, $id, $reply_to);
                    }
                    break;
                case 'u':
                case 'c': {

                        $id = mysql_clean($request['obj_id']);
                        $comment = $request['comment'];
                        if ($comment == 'undefined')
                            $comment = '';
                        $reply_to = $request['reply_to'];

                        $cid = $userquery->add_comment($comment, $id, $reply_to);
                    }
                    break;
                case 't':
                case 'topic': {

                        $id = mysql_clean($request['obj_id']);
                        $comment = $request['comment'];
                        if ($comment == 'undefined')
                            $comment = '';
                        $reply_to = $request['reply_to'];

                        $cid = $cbgroup->add_comment($comment, $id, $reply_to);
                    }
                    break;

                case 'cl':
                case 'collection': {
                        $id = mysql_clean($request['obj_id']);
                        $comment = $request['comment'];
                        if ($comment == 'undefined')
                            $comment = '';
                        $reply_to = $request['reply_to'];

                        $cid = $cbcollection->add_comment($comment, $id, $reply_to);
                    }
                    break;

                case "p":
                case "photo": {
                        $id = mysql_clean($request['obj_id']);
                        $comment = $request['comment'];
                        if ($comment == 'undefined')
                            $comment = '';
                        $reply_to = $request['reply_to'];
                        $cid = $cbphoto->add_comment($comment, $id, $reply_to);
                    }
                    break;
            }


            if (error()) {
                exit(json_encode(array('err' => error(), 'session' => $_COOKIE['PHPSESSID'])));
            }

            $comment = $myquery->get_comment($cid);

            $array = array(
                'msg' => msg(),
                'comment' => $comment,
                'success' => 'ok',
                'cid' => $cid
            );

            echo json_encode($array);
        }
        break;

        case "create_playlist":
        case "addPlaylist":
        case "add_playlist":
        {
            $array = array(
                'name',
                'description',
                'tags',
                'playlist_type',
                'privacy',
                'allow_comments',
                'allow_rating',
                'type',
            );

            $type = $request['type'];

            $input = array();
            foreach ($array as $ar) {
                $input[$ar] = mysql_clean($request[$ar]);
            }


            if ($type == 'v' || !isset($type))
                $pid = $cbvid->action->create_playlist($input);

            if (!$type)
                e(lang("Invalid playlist type"));
	
			if(VERSION>2.6)
					$rel = get_rel_list();
					
					
            if (error()) {
				
				$rel = array();
				
				
				
                echo json_encode(array('err' => error(), 'rel' => $rel));
            } else {
                $playlist = $cbvid->action->get_playlist($pid);

                echo json_encode(array('success' => 'yes', 'rel' => $rel,
                'pid' => $pid, 'playlist' => $playlist,
                'msg' => msg()));
            }
        }
        break;

        case "delete_playlist": {
            $pid = mysql_clean($request['playlist_id']);
            $cbvid->action->delete_playlist($pid);

            if (error()) {
                echo json_encode(array('err' => error()));
            } else {
                echo json_encode(array('msg' => array(lang('Playlist has been removed'))));
            }
        }
        break;

        case "add_playlist_item": {

            $type = $request['type'];
            $pid = mysql_clean($request['playlist_id']);
            $id = mysql_clean($request['object_id']);
            // $note = mysql_clean(post('note'));

            switch ($type) {
                case 'v':
                default: {
                        $item_id = $cbvid->action->add_playlist_item($pid, $id);

                        if (!error()) {
                            updateObjectStats('plist', 'video', $id);
                            echo json_encode(array('status' => 'ok',
                                'msg' => msg(), 'item_id' => $item_id, 'updated' => nicetime(now())));
                        } else {
                            echo json_encode(array('err' => error()));
                        }
                    }
            }
        }
        break;
        
        case "remove_playlist_item":
        {
            $item_id = mysql_clean($request['item_id']);
            $cbvid->action->delete_playlist_item($item_id);
            if(error())
                echo json_encode(array('err'=>error()));
            else
                echo json_encode(array('success'=>'ok'));
        }
        break;
        
        case "insert_video": {
            $title = $request['title'];
            $file_name = time().  RandomString(5);
            
            $file_directory = createDataFolders();
            
            $vidDetails = array
                (
                'title' => $title,
                'description' => $title,
                'tags' => genTags(str_replace(' ', ', ', $title)),
                'category' => array($cbvid->get_default_cid()),
                'file_name' => $file_name,
                'file_directory' => $file_directory,
                'userid' => userid(),
            );

            $vid = $Upload->submit_upload($vidDetails);

            echo json_encode(array('success' => 'yes', 
                'vid' => $vid,'file_directory'=>$file_directory,
                'file_name'=>$file_name));
        }
        break;
    
    case "update_video":{
        
            //Setting up the categories..
            $request['category']        = explode(',',$request['category']);
            $request['videoid']         = trim($request['videoid']);
            
            $_POST = $request;
            
            $Upload->validate_video_upload_form();
            
            if (empty($eh->error_list)) {
                $cbvid->update_video();
            }
            
            
            
            if (error())
                echo json_encode(array('error' => error('single')));
            else
                echo json_encode(array('msg' => msg('single')));
        
    }
    
    
    break;
    case "rating":
    {
        
        $type = mysql_clean($request['type']);
        $id = mysql_clean($request['id']);
        $rating = mysql_clean($request['rating']);
        
        switch($type){
            case "video":
            case "v":
            {
                $result = $cbvid->rate_video($id,$rating);
                echo json_encode(array('success'=>'ok'));
            }
            break;

            case "photo":
            {
                $rating = $_POST['rating']*2;
                $id = $_POST['id'];
                $result = $cbphoto->rate_photo($id,$rating);
                $result['is_rating'] = true;
                $cbvid->show_video_rating($result);

                $funcs = cb_get_functions('rate_photo');	
                if($funcs)
                foreach($funcs as $func)
                {
                        $func['func']($id);
                }
            }
            break;
            case "collection":
            {
                $rating = $_POST['rating']*2;
                $id = $_POST['id'];
                $result = $cbcollection->rate_collection($id,$rating);
                $result['is_rating'] = true;
                $cbvid->show_video_rating($result);

                $funcs = cb_get_functions('rate_collection');	
                if($funcs)
                foreach($funcs as $func)
                {
                        $func['func']($id);
                }
            }
            break;

            case "user":
            {
                $rating = $_POST['rating']*2;
                $id = $_POST['id'];
                $result = $userquery->rate_user($id,$rating);
                $result['is_rating'] = true;
                $cbvid->show_video_rating($result);

                $funcs = cb_get_functions('rate_user');	
                if($funcs)
                foreach($funcs as $func)
                {
                        $func['func']($id);
                }
            }
            break;
        }
    }
    break;
    
    case 'flag_object':
    {
        $type = strtolower($request['type']);
        $id = $request['id'];
        switch($type)
        {
            case 'v':
            case 'video':
            default:
            {
                    
                    $reported = $cbvideo->action->report_it($id);
            }
            break;

            case 'g':
            case 'group':
            default:
            {
                   // $id = $_POST['id'];
                    $cbgroup->action->report_it($id);
            }
            break;

            case 'u':
            case 'user':
            default:
            {
                    //$id = $_POST['id'];
                    $userquery->action->report_it($id);
            }
            break;

            case 'p':
            case 'photo':
            {
                    //$id = $_POST['id'];
                    $cbphoto->action->report_it($id);
            }
            break;

            case "cl":
            case "collection":
            {
                    //$id = $_POST['id'];
                    $cbcollection->action->report_it($id);
            }
            break;

        }

        if(msg())
        {
            $msg = msg_list();
            echo json_encode(array('success'=>'yes','msg'=>$msg[0]));
        }
        if(error())
        {
            $msg = error_list();
            echo json_encode(array('err'=>$msg[0]));
        }

    }
    break;
    
    default:
        exit(json_encode(array('err'=>array(lang('Invalid request')))));
}
?>