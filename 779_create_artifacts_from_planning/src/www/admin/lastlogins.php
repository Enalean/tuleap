<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    


session_require(array('group'=>'1','admin_flags'=>'A'));

$res_logins = db_query("SELECT session.user_id AS user_id,"
	. "session.ip_addr AS ip_addr,"
	. "session.time AS time,"
	. "user.user_name AS user_name FROM session,user "
	. "WHERE session.user_id=user.user_id AND "
	. "session.user_id>0 AND session.time>0 ORDER BY session.time DESC LIMIT 5000");
if (db_numrows($res_logins) < 1) exit_error("No records found","There must be an error somewhere.");

$HTML->header(array('title'=>$Language->getText('admin_lastlogins','title')));

print '<P><B>'.$Language->getText('admin_lastlogins','recent').'</B>';
print "\n<P>";
print '<TABLE width=100% cellpadding=0 cellspacing=0 border=0>';

while ($row_logins = db_fetch_array($res_logins)) {
	print '<TR>';
	print "<TD>$row_logins[user_name]</TD>";
	print "<TD>$row_logins[ip_addr]</TD>";
	print "<TD>" . date($Language->getText('system','datefmt'),$row_logins['time']) . "</TD>";
	print '</TR>';
}

print '</TABLE>';

$HTML->footer(array());

?>
