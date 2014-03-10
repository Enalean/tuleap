<?php
require_once('pre.php');

$valid = new Valid_UInt('tracker');
$valid->required();
if($request->valid($valid)) {
    if (user_get_preference('tracker_comment_invertorder_'.$request->get('tracker'))) {
        user_del_preference('tracker_comment_invertorder_'.$request->get('tracker'));
    } else {
        user_set_preference('tracker_comment_invertorder_'.$request->get('tracker'), '1');
    }
}

?>