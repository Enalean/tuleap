<?php
// ## export sf front page news in RSS
include "pre.php";
include "rss_utils.inc";
header("Content-Type: text/plain");
print '<?xml version="1.0"?>
<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">
';
// ## default limit
if (!$limit) $limit = 10;
if ($limit > 100) $limit = 100;

$res = db_query("SELECT groups.group_name AS group_name,"
	. "groups.group_id AS group_id,"
	. "groups.unix_group_name AS unix_group_name,"
	. "groups.short_description AS short_description,"
	. "groups.license AS license,"
	. "groups.file_downloads AS file_downloads,"
	. "user.user_name AS user_name,"
	. "user.user_id AS user_id,"
	. "filemodule.filemodule_id AS filemodule_id,"
	. "filemodule.module_name AS module_name,"
	. "filerelease.release_time AS release_time,"
	. "filerelease.filename AS filename,"
	. "filerelease.release_version AS release_version,"
	. "filerelease.filerelease_id AS filerelease_id "
	. "FROM user,filerelease,filemodule,groups WHERE "
	. "filerelease.user_id=user.user_id AND "
	. "filerelease.group_id=groups.group_id AND "
	. "filerelease.filemodule_id=filemodule.filemodule_id "
	. "ORDER BY filerelease.release_time DESC LIMIT ".($limit * 3));


// ## one time output
print " <channel>\n";
print "  <copyright>Copyright 1999-2000 VA Linux Systems, Inc.</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>SourceForge New Releases</description>\n";
print "  <link>http://$GLOBALS[sys_default_domain]</link>\n";
print "  <title>SourceForge New Releases</title>\n";
print "  <webMaster>webmaster@$GLOBALS[sys_default_domain]</webMaster>\n";
print "  <language>en-us</language>\n";
// ## item outputs
$outputtotal = 0;
while ($row = db_fetch_array($res)) {
	if (!$G_RELEASE["$row[group_id]"]) {
		print "  <item>\n";
		print "   <title>".htmlspecialchars($row[group_name])."</title>\n";
		print "   <link>http://$GLOBALS[sys_default_domain]/project/filelist.php?group_id=$row[group_id]</link>\n";
		print "   <description>".rss_description($row[short_description])."</description>\n";
		print "  </item>\n";
		$outputtotal++;
	}
	// ## eliminate dupes, only do $limit of these
	$G_RELEASE["$row[group_id]"] = 1;
	if ($outputtotal >= $limit) break;
}
// ## end output
print " </channel>\n";
?>
</rss>
