<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 
//
// Modified by Laurent Julliard, Xerox.
//

require_once('pre.php');
require_once('common/mail/Mail.class.php');
require_once('www/include/account.php');
require_once('www/project/admin/ugroup_utils.php');


if (user_isloggedin()) {
	$user_id = user_getid();

    $vGroupId = new Valid_GroupId();
    $vGroupId->required();
    if($request->valid($vGroupId)) {
        $group_id = $request->get('group_id');
    } else {
        exit_no_group();
    }
    //Process MEMBERSHIP_DELETE event
    account_remove_user_from_group($group_id,$user_id, false);

	/********* mail the changes so the admins know what happened *********/
	$res_admin = db_query("SELECT user.user_id AS user_id, user.email AS email, user.user_name AS user_name FROM user,user_group "
		. "WHERE user_group.user_id=user.user_id AND user_group.group_id=".db_ei($group_id)." AND "
		. "user_group.admin_flags = 'A'");
    $to = '';
	while ($row_admin = db_fetch_array($res_admin)) {
		$to .= "$row_admin[email],";
	}
	if(strlen($to) > 0) {
		$to = substr($to,0,-1);
	
        $project=new Project($group_id);
	    $project_name = $project->getPublicName();

        list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
	    $link_members = get_server_url()."/project/memberlist.php?group_id=$group_id";
	    $subject = $Language->getText('bookmark_rmproject', 'mail_subject', array($GLOBALS['sys_name'],user_getname($user_id),$project_name));
	    $body = stripcslashes($Language->getText('bookmark_rmproject', 'mail_body', array($project_name, user_getname($user_id),$link_members)));
	    $mail = new Mail();
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setBody($body);
        $mail->send();
    }
	// display the personal page again
	session_redirect("/my/");

} else {

	exit_not_logged_in();

}

?>
