<?php
  // Copyright (c) Enalean, 2016. All Rights Reserved.
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  // 
  // ## export sf front page news in RSS

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/new/new_utils.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require('./rss_utils.inc');

$request = HTTPRequest::instance();

header("Content-Type: text/xml");

print '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="0.91">
';
// ## default limit
$limit          = 10;
$validator_uint = new Valid_UInt('limit');
if ($request->valid($validator_uint)) {
  $limit = $request->get('limit');
}
if ($limit > 100) {
  $limit = 100;
}

$query = new_utils_get_new_releases_long(0, 0, $limit*3);
$res   = db_query($query);

// ## one time output
print " <channel>\n";
print "  <copyright>".$Language->getText(
        'export_rss_sfnewreleases',
        'copyright',
        array(ForgeConfig::get('sys_long_org_name'),ForgeConfig::get('sys_name'),date('Y',time()))
    )."</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>".$Language->getText(
        'export_rss_sfnewreleases',
        'new_releases',
        ForgeConfig::get('sys_name')
    ) ."</description>\n";
print "  <link>". $request->getServerUrl() ."</link>\n";
print "  <title>".$Language->getText('export_rss_sfnewreleases','new_releases',ForgeConfig::get('sys_name'))."</title>\n";
print "  <language>en-us</language>\n";
// ## item outputs
$outputtotal = 0;
$frspf       = new FRSPackageFactory();
$frsrf       = new FRSReleaseFactory();
$G_RELEASE   = array();

while ($row = db_fetch_array($res)) {
  if (!$G_RELEASE[$row['group_id']]) {
    if ($frspf->userCanRead($row['group_id'], $row['package_id'], 100) &&
        $frsrf->userCanRead($row['group_id'], $row['package_id'], $row['release_id'], 100)) {
      print "  <item>\n";
      print "   <title>".htmlspecialchars($row['group_name']." ". $row['release_version'])."</title>\n";
      print "   <link>". $request->getServerUrl() ."/file/showfiles.php?group_id=" . urlencode($row['group_id']) . "</link>\n";
      print "   <description>".rss_description($row['short_description'])."</description>\n";
      print "  </item>\n";
      $outputtotal++;
	
      // ## eliminate dupes, only do $limit of these
      $G_RELEASE[$row['group_id']] = 1;
      if ($outputtotal >= $limit) break;
    }
  }
 }
// ## end output
print " </channel>\n</rss>";
