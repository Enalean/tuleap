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
'Your project registration for CodeX has been approved. 

Project Full Name:  '.$row_grp['group_name'].'
Project Unix Name:  '.$row_grp['unix_group_name'].'
CVS Server:         cvs.'.$row_grp['unix_group_name'].'.'.$GLOBALS['sys_default_domain'].'
Shell/Web Server:   '.$row_grp['unix_group_name'].'.'.$GLOBALS['sys_default_domain'].'

Your DNS (Domain Name Server)  will take up to a day to be aware of these
new names. Your shell accounts will become active at the next '.$GLOBALS['sys_crondelay'].'-hour cron
update. While waiting for your DNS to resolve, you may try shelling into 
'. $GLOBALS['sys_shell_host']. ' and pointing CVS to '. $GLOBALS['sys_cvs_host'].'.

If after '.$GLOBALS['sys_crondelay'].' hours your shell account or your CVS access still do not work,
please open a support ticket so that we may take a look at the problem.
Please note that all shell accounts can be accessed through telnet and
or SSH (version 1) if you prefer a secure connection.

Your web site hosting area is accessible through your shell account.
Directory information will be displayed immediately after logging in.

Please take some time to read the site documentation about the tools
and services offered by CodeX to project administrators (see the
"Site documentation" menu item on the left hand side).

We now invite you to visit the public summary page of your
project at http://codex.xerox.com/projects/'.$row_grp['unix_group_name'].', create
a short public description for your project and categorize it in the
Trove Software Map.

If you visit your own project page in CodeX while logged in (select
"My Personal Page" and then one of your registered projects), you will
find additional menu functions to your left labeled "Project Admin". 
The admin page allows you to fully administrate your project environment
you can create create mailing lists, forums, manage your tasks, bugs,etc.
and why not publish your first project news to advertise its creation
(we\'ll put it on the front page !).


A side comment on CVS: if you already have a CVS tree and want to
transfer it as is on CodeX then contact us. We\'ll need a tar/gzip or
zipped file of your entire document root, including CVSROOT directory.
This will preserve your revision history. If you do not care about
preserving the existing CVS history then just do a "cvs import" 
yourself.

Enjoy the system, and please tell other Xerox employees about CodeX.
The CodeX team believes in the value of code sharing inside Xerox and
we rely on all of you to preach the word. Let\'s grow the Xerox Inner
Source community !

Let us know if there is anything we can do to help you.

 -- the CodeX team';

// LJ Uncomment to test
//echo $message;
	
// LJ Comment below to test (avoid sending real e-mail)

	mail($row_admins['email'],'CodeX Project '.$row_grp['unix_group_name'].' Approved',$message,"From: noreply@$GLOBALS[sys_default_domain]");

}

}

// LJ To test the new e-mail message content and format
// LJ uncomment the code below and above and invoke 
// LJ http://codex.xerox.com/include/proj_email.php
// LJ from your favorite browser
//LJ
//require("pre.php");
//echo "<PRE>";
//send_new_project_email(4);
//echo "</PRE>";
?>
