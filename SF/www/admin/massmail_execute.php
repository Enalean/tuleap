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

switch ($destination) {
	case 'comm': 
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A' AND mail_va=1");
		break;
	case 'sf':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A' AND mail_siteupdates=1");
		break;
	case 'all':
		$res_mail = db_query("SELECT email,user_name FROM user WHERE status='A'");
		break;
	case 'admin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND user.status='A' AND user_group.admin_flags='A' "
		."GROUP by user.user_id");
		break;
	case 'sfadmin':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "	
		."user.user_id=user_group.user_id AND user.status='A' AND user_group.group_id=1 "
		."GROUP by user.user_id");
		break;
	case 'devel':
		$res_mail = db_query("SELECT user.email AS email,user.user_name AS user_name "
		."FROM user,user_group WHERE "
		."user.user_id=user_group.user_id AND user.status='A' GROUP BY user.user_id");
		break;
	default:
		exit_error('Unrecognized Post','cannot execute');
}

print "Query Complete. Beginning mailings to ".db_numrows($res_mail)."\n\n";
flush();

$rows=db_numrows($res_mail);

for ($i=0; $i<$rows; $i++) {
	$tolist .= db_result($res_mail,$i,'email').', ';
	if ($i % 25 == 0) {
		//spawn sendmail for 25 addresses at a time
		$body = "To: noreply@$GLOBALS[HTTP_HOST]".
			"\nBCC: $tolist".
			"\nSubject: ". stripslashes($mail_subject).
			"\n\n". stripslashes($mail_message);
		exec ("/bin/echo \"". util_prep_string_for_sendmail($body) ."\" | /usr/sbin/sendmail -fnoreply@$GLOBALS[HTTP_HOST] -t -i &");
		usleep(2000000);
		print "\nsending to $tolist";
		$tolist='';
		flush();
	}
}

//send the last of the messages.
//spawn sendmail for 25 addresses at a time
$body = "To: noreply@$GLOBALS[HTTP_HOST]".
"\nBCC: $tolist".
"\nSubject: ". stripslashes($mail_subject).
"\n\n". stripslashes($mail_message);
exec ("/bin/echo \"". util_prep_string_for_sendmail($body) ."\" | /usr/sbin/sendmail -fnoreply@$GLOBALS[sys_default_domain] -t -i &");
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
		"From: SourceForge <noreply@$GLOBALS[sys_default_domain]>");
	usleep(250000);
	flush();
}
*/

?>
