<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

function send_new_project_email($group_id) {

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
	$message = 
'Your project registration has been approved. 

Project Full Name:    '.$row_grp['group_name'].'
Project Unix Name:    '.$row_grp['unix_group_name'].'
Project Summary Page: http://'.$GLOBALS['sys_default_domain'].'/projects/'.$row_grp['unix_group_name'].'
Project Web Server:   http://'.$row_grp['unix_group_name'].'.'.$GLOBALS['sys_default_domain'].'
CVS Server:           cvs.'.$row_grp['unix_group_name'].'.'.$GLOBALS['sys_default_domain'].'
Shell Server:         '.$row_grp['unix_group_name'].'.'.$GLOBALS['sys_default_domain'].'

Please take some time to read the site documentation about the tools
and services offered by '.$GLOBALS['sys_name'].' to project
administrators. Most of the documentation (including a detailed User
Guide) is available under the "Site documentation" link on the left
hand side menu of the '.$GLOBALS['sys_name'].' Home page.

We now invite you to visit the Public Summary page of your project at
http://'.$GLOBALS['sys_default_domain'].'/projects/'.$row_grp['unix_group_name'].',
create a short public description for your project and categorize it
in the Software Map. This will be immensely helpful to the '.$GLOBALS['sys_name'].' visitors.

Once on your Project Summary Page you will see a "Project Admin" link
on the left hand side. This Admin. page allows you to fully
administrate your project environment you can create create mailing
lists, forums, manage your tasks, bugs,etc.  and why not publish your
first project news to advertise its creation (we\'ll put it on the
front page !).

Other miscellaneous points:

- Your Shell account will become active at the next
'.$GLOBALS['sys_crondelay'].'-hour cron update.  If after
'.$GLOBALS['sys_crondelay'].' hours your shell account or your CVS
access still does not work, please open a support ticket so that we
may take a look at the problem.

- Also note that it might take up to a day for the Xerox name servers
to be aware of your project specific server names (see above). If you
are in a hurry, you may try shelling into
'. $GLOBALS['sys_shell_host']. ' and pointing your CVS client to
'. $GLOBALS['sys_cvs_host'].'.

- Your web site hosting area (Project Web Server) is accessible
through your shell account, ftp or as Windows shared resource.(See the
'.$GLOBALS['sys_name'].' User Guide for more details).

- A side comment on CVS: if you already have a CVS repository of your
own and want to transfer it as is on '.$GLOBALS['sys_name'].' then
contact us. We\'ll need a tar/gzip or zipped file of your entire
document root, including the top CVSROOT directory.  This will
preserve your revision history. If you do not care about preserving
the existing CVS history then just do a "cvs import" yourself.

Enjoy the system, and please tell other Xerox employees about
'.$GLOBALS['sys_name'].'.  The '.$GLOBALS['sys_name'].' Team believes
in the value of code sharing inside Xerox and we rely on all of you to
preach the word. Let\'s grow the Xerox Source Code Sharing community !

Let us know if there is anything we can do to help you.

 -- The '.$GLOBALS['sys_name'].' Team';

// LJ Uncomment to test
//echo $message;
	
// LJ Comment below to test (avoid sending real e-mail)

	mail($row_admins['email'],$GLOBALS['sys_name'].' Project '.$row_grp['unix_group_name'].' Approved',$message,"From: noreply@$GLOBALS[sys_default_domain]");

}

}

//
// send mail notification to new registered user
//
function send_new_user_email($to,$confirm_hash)
{
    // if the HTTP server has SSL enabled then favor confirmation through SSL
    if ($GLOBALS['sys_https_host'] != "") {
	$base_url = "https://".$GLOBALS['sys_https_host'];
    } else {
	$base_url = "http://".$GLOBALS['sys_default_domain'];
    }

    $message = "Thank you for registering on the ".$GLOBALS['sys_name']." web site. In order\n"
	. "to confirm your registration you must visit the following url: \n\n"
	. "<". $base_url ."/account/verify.php?confirm_hash=$confirm_hash>\n\n"
	. "Enjoy the site.\n\n"
	. " -- The ".$GLOBALS['sys_name']." Team\n";
    
    mail($to, $GLOBALS['sys_name']." Account Registration",$message,"From: noreply@".$GLOBALS['sys_default_domain']);

}

// LJ To test the new e-mail message content and format
// LJ uncomment the code below and above and invoke 
// LJ http://codex.xerox.com/include/proj_email.php
// LJ from your favorite browser
//LJ
//require("pre.php");
//echo "<PRE>";
//send_new_project_email(4);
//send_new_project_email("julliard@xrce.xerox.com");
//echo "</PRE>";
?>
