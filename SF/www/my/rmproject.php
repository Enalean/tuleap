<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Thanks to Wallace Lee for submitting this

*/

require ('pre.php');

if (user_isloggedin()) {
	$user_id = user_getid();

	// make sure that user is not an admin
	$result=db_query("SELECT admin_flags FROM user_group WHERE user_id='$user_id' AND group_id='$group_id'");
	if (!$result || db_numrows($result) < 1) {
		exit_error('Error','You are not a member of that project.');
	}
	$row_flags = db_fetch_array($result);

	if (ereg("A",$row_flags['admin_flags'],$ereg_match)) {
		exit_error("Error Removing Group Member","You cannot remove a group administrator. "
		. "Remove this persons admininstrator privileges on the user permissions page before attempting to remove them from the project. </A> ");
	} 
       
	db_query("DELETE FROM user_group WHERE user_id='$user_id' AND group_id='$group_id'");
	session_redirect("/my/");

	/********* mail the changes so the admins know what happened *********/
	$res_admin = db_query("SELECT user.user_id AS user_id, user.email AS email, user.user_name AS user_name FROM user,user_group "
		. "WHERE user_group.user_id=user.user_id AND user_group.group_id=$group_id AND "
		. "user_group.admin_flags = 'A'");

	while ($row_admin = db_fetch_array($res_admin)) {
		$to .= "$row_admin[email],";
	}
	if($to) {
		$to = substr($to,0,-1);
	}
	$other = "From: noreply@$GLOBALS[sys_default_domain]";
	$subject = "[SourceForge] $user_id has been removed from project $group_id";
	$body = "This message is being sent to notify the administrator(s) of".
		"\nproject ID $group_id that user ID $user_id has chosen to".
		"\nremove him/herself from the project.".
		"\n\nFollow this link to see the current members of your project:".
		"\nhttp://$GLOBALS[sys_default_domain]/project/memberlist.php?group_id=$group_id".
		"\n\n";

	mail($to,$subject,$body,$other);

} else {

	exit_not_logged_in();

}

?>
