<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function send_new_project_email($group_id) {
	global $sys_show_project_type;

	$res_grp = db_query("SELECT * FROM groups WHERE group_id='$group_id'");

	if (db_numrows($res_grp) < 1) {
		echo ("Group [ $group_id ] does not exist. Shame on you, sysadmin.");
	}

	$row_grp = db_fetch_array($res_grp);

	$res_admins = db_query("SELECT user.user_name,user.email FROM user,user_group WHERE "
		. "user.user_id=user_group.user_id AND user_group.group_id='$group_id' AND "
		. "user_group.admin_flags='A'");

	if (db_numrows($res_admins) < 1) {
		echo ("Group [ $group_id ] does not seem to have any administrators.");
	}

	// send one email per admin
	while ($row_admins = db_fetch_array($res_admins)) {

	if ( $sys_show_project_type ) {
		$res_type = db_query("SELECT * FROM project_type WHERE project_type_id = ". $row_grp[project_type]);
		$row_type = db_fetch_array($res_type);
		$message_project_type = "\nProject Type:         ".$row_type[description];
	}

        $server = get_server_url();
	// $message is defined in the content file
	include(util_get_content('include/new_project_email'));

	// LJ Uncomment to test
	//echo $message; return
	$hdrs = "From: ".$GLOBALS['sys_email_admin'].$GLOBALS['sys_lf'];
	$hdrs .='Content-type: text/plain; charset=iso-8859-1'.$GLOBALS['sys_lf'];
	
	mail($row_admins['email'],$GLOBALS['sys_name'].' Project '.$row_grp['unix_group_name'].' Approved',$message,$hdrs);

}

}

//
// send mail notification to new registered user
//
function send_new_user_email($to,$confirm_hash)
{
    $base_url = get_server_url();

    // $message is defined in the content file
    include(util_get_content('include/new_user_email'));
    
    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
    mail($to, $GLOBALS['sys_name']." Account Registration",$message,"From: noreply@".$host);

}

// LJ To test the new e-mail message content and format
// LJ uncomment the code below and above and invoke 
// LJ http://codex.xerox.com/include/proj_email.php
// LJ from your favorite browser
//LJ
//require($DOCUMENT_ROOT.'/include/pre.php');
//echo "<PRE>";
//send_new_project_email(4);
//send_new_project_email("julliard@xrce.xerox.com");
//echo "</PRE>";
?>
