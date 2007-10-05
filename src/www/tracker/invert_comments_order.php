<?php
require_once('pre.php');
if (user_get_preference('tracker_comment_invertorder')) {
    user_del_preference('tracker_comment_invertorder');
} else {
    user_set_preference('tracker_comment_invertorder', '1');
}
?>
