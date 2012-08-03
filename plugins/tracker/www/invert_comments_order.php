<?php
require_once('pre.php');

$valid = new Valid_UInt('tracker_id');
$valid->required();
if($request->valid($valid)) {
    if (user_get_preference('tracker_comment_invertorder_'.$request->get('tracker_id'))) {
        user_del_preference('tracker_comment_invertorder_'.$request->get('tracker_id'));
    } else {
        user_set_preference('tracker_comment_invertorder_'.$request->get('tracker_id'), '1');
    }
}

?>