<?php
// ## export sf front page news in RSS
include "pre.php";
include "rss_utils.inc";
header("Content-Type: text/plain");
print '<?xml version="1.0"?>
<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">
';
$res = db_query('SELECT group_id,group_name,homepage,short_description FROM groups '
	.'WHERE is_public=1 AND status=\'A\' ORDER BY group_id'.($limit?" LIMIT $limit":""));

// ## one time output
print " <channel>\n";
print "  <copyright>Copyright 1999-2000 VA Linux Systems, Inc.</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>SourceForge Full Project Listing</description>\n";
print "  <link>http://$GLOBALS[sys_default_domain]</link>\n";
print "  <title>SourceForge Full Project Listing</title>\n";
print "  <webMaster>webmaster@$GLOBALS[sys_default_domain]</webMaster>\n";
print "  <language>en-us</language>\n";
// ## item outputs
while ($row = db_fetch_array($res)) {
	print "  <item>\n";
	print "   <title>".htmlspecialchars($row[group_name])."</title>\n";
	print "   <link>http://$GLOBALS[sys_default_domain]/project/?group_id=$row[group_id]</link>\n";
	print "   <description>";
	print ereg_replace(" *\r*\n *"," ",rss_description($row[short_description]));
	print "</description>\n";
	print "  </item>\n";
}
// ## end output
print " </channel>\n";
?>
</rss>
