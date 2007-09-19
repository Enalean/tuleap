<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
require_once('pre.php');
require_once('www/project/admin/permissions.php');

$request =& HTTPRequest::instance();
if ($request->exist('group_id')) {
    $group_id = $request->get('group_id');
    session_require(array('group'=>$group_id,'admin_flags'=>'A'));
    
    echo '<table><tr><td><div style="overflow:auto; height:250px; border:1px solid gray">';
    
    // First make a quick hash of this project's restricted users
    $current_group_restricted_users=array();
    $sql="SELECT user.user_id from user, user_group WHERE user.status='R' AND user.user_id=user_group.user_id AND user_group.group_id=$group_id";
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
        $current_group_restricted_users[$row['user_id']] = true;
    }
    
    $sql="SELECT user_id, user_name, realname, status FROM user WHERE status='A' OR status='R' ORDER BY user_name";
    $res = db_query($sql);
    $member_id = array();
    while ($row = db_fetch_array($res)) {
        // Don't display restricted users that don't belong to the project
        if ($row['status']=='R') { 
            if (!isset($current_group_restricted_users[$row['user_id']]) || !$current_group_restricted_users[$row['user_id']]) {
                continue;
            }
        }
        echo '<div><b>'.$row['user_name'].'</b> ('.addslashes($row['realname']).")</div>\n";
    }
    
    echo '</div></td></tr></table>';
} else {
    $feedback = new Feedback();
    $feedback->log('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
    echo $feedback->fetch();
}
?>
