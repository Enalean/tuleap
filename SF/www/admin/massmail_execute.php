<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');
session_require(array('group'=>1,'admin_flags'=>'A'));

header ('Content-Type: text/plain');
print "Received Post. Making Query.\n";
flush();

// LJ The to_name variable has been added here to be used
// LJ in the sendmail command later in this script
switch ($destination) {
	case 'comm': 
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A' AND mail_va=1");
		$to_name = 'Additional Community Mailings Subcribers';
		break;
	case 'sf':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A' AND mail_siteupdates=1");
		$to_name = 'Site Updates Subcribers';
		break;
	case 'all':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A'");
		$to_name = 'All Users';
		break;
	case 'admin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND user.status='A' AND user_group.admin_flags='A' "
		."GROUP by user.user_id");
		$to_name = 'Project Administrators';
		break;
	case 'sfadmin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND user.status='A' AND user_group.group_id=1 "
		."GROUP by user.user_id");
		$to_name = 'CodeX Administrators';
		break;
	case 'devel':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "
		."user.user_id=user_group.user_id AND user.status='A' GROUP BY user.user_id");
		$to_name = 'Project Developers';
		break;
	default:
		exit_error('Unrecognized Post','cannot execute');
}

print "Query Complete. Beginning mailings to ".db_numrows($res_mail)."\n\n";
flush();

$rows=db_numrows($res_mail);

//LJ -fnoreply@... replaced with -fcodex-admin@...
//LJ and to_name explicit name added in the To: field
for ($i=0; $i<$rows; $i++) {
	$tolist .= db_result($res_mail,$i,'email').', ';
	if ($i % 25 == 0) {
		//spawn sendmail for 25 addresses at a time
		$body = "To: \"$to_name\" <noreply@$GLOBALS[HTTP_HOST]>".
			"\nBCC: $tolist".
			"\nSubject: ". stripslashes($mail_subject).
			"\n\n". stripslashes($mail_message);
		exec ("/bin/echo \"". util_prep_string_for_sendmail($body) ."\" | /usr/sbin/sendmail -fcodex-admin@$GLOBALS[HTTP_HOST] -t -i &");
		usleep(2000000);
		print "\nsending to $tolist";
		$tolist='';
		flush();
	}
}

//send the last of the messages.
//spawn sendmail for 25 addresses at a time
//LJ and to_name explicit name added in the To: field
$body = "To: \"$to_name\" <noreply@$GLOBALS[HTTP_HOST]>".
"\nBCC: $tolist".
"\nSubject: ". stripslashes($mail_subject).
"\n\n". stripslashes($mail_message);

exec ("/bin/echo \"". util_prep_string_for_sendmail($body) ."\" | /usr/sbin/sendmail -fcodex-admin@$GLOBALS[sys_default_domain] -t -i &");
usleep(2000000);
print "\nsending to $tolist";
$tolist='';
flush();


/*
while ($row_mail = db_fetch_array($res_mail)) {
	print "sending to $row_mail[user_name] <$row_mail[email]>\n";
	mail("$row_mail[user_name] <$row_mail[email]>",
		stripslashes($GLOBALS['mail_subject']),
		stripslashes($GLOBALS['mail_message']),
		"From: CodeX <noreply@$GLOBALS[sys_default_domain]>");
	usleep(250000);
	flush();
}
*/

?>
