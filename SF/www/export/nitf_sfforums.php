<?php
// ## export sf front page news in RSS
require_once('pre.php');
header("Content-Type: text/xml");

// ## group_id must be specified
$res_grp = db_query('SELECT group_id,group_name FROM groups '
	.'WHERE is_public=1 AND status=\'A\' AND group_id='.$group_id);
if (db_numrows($res_grp) < 1) {
	print 'ERROR: This URL must be called with a valid group_id parameter';
	exit;
} else {
	$row_grp = db_fetch_array($res_grp);
}

print '<?xml version="1.0"?>
<!DOCTYPE sf_forum SYSTEM "'.get_server_url().'/exports/sf_forum_0.1.dtd">
';
print "<group name=\"$row_grp[group_name]\">";

$res_forum = db_query('SELECT group_forum_id,forum_name FROM forum_group_list '
	.'WHERE group_id='.$group_id);

while ($row_forum = db_fetch_array($res_forum)) {
	print " <forum name=\"$row_forum[forum_name]\">\n";

	$res_post = db_query('SELECT forum.msg_id AS msg_id,forum.subject AS subject,'
		.'forum.body AS body,forum.date AS date,user.user_name AS user_name,'
		.'user.realname AS realname FROM forum,user '
		.'WHERE forum.posted_by=user.user_id AND forum.group_forum_id='
		.$row_forum[group_forum_id]);


	// ## item outputs
	while ($row_post = db_fetch_array($res_post)) {
		print "  <nitf version=\"XMLNews/DTD XMLNEWS-STORY 1.8//EN\">\n";
		print "   <head>\n";
		print "    <title>$row_post[subject]</title>\n";
		print "   </head>\n";
		print "   <body><body.content><block>\n";
		print $row_post[body];
		print "   </block></body.content></body>\n";
		print "  </nitf>\n";
	}
	print " </forum>\n";
}

print " </group>\n";
?>
