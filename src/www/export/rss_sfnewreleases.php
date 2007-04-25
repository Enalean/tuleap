<?php
  //
  // SourceForge: Breaking Down the Barriers to Open Source Development
  // Copyright 1999-2000 (c) The SourceForge Crew
  // http://sourceforge.net
  //
  // 
  // ## export sf front page news in RSS

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/new/new_utils.php');
require('./rss_utils.inc');
$Language->loadLanguageMsg('export/export');

header("Content-Type: text/xml");
print '<?xml version="1.0" encoding="ISO-8859-1" ?>
<rss version="0.91">
';
// ## default limit
if (!$limit) $limit = 10;
if ($limit > 100) $limit = 100;

/**
$query = "SELECT groups.group_name AS group_name,"
  . "groups.group_id AS group_id,"
  . "groups.unix_group_name AS unix_group_name,"
  . "groups.short_description AS short_description,"
  . "groups.license AS license,"
  . "user.user_name AS user_name,"
  . "user.user_id AS user_id,"
  . "frs_release.release_id AS release_id,"
  . "frs_release.name AS release_version,"
  . "frs_release.release_date AS release_date,"
  . "frs_release.released_by AS released_by,"
  . "frs_package.name AS module_name, "
  . "frs_package.package_id AS package_id, "
  . "frs_dlstats_grouptotal_agg.downloads AS downloads "
  . "FROM groups,user,frs_package,frs_release,frs_dlstats_grouptotal_agg "
  . "WHERE ( frs_release.package_id = frs_package.package_id "
  . "AND frs_package.group_id = groups.group_id "
  . "AND frs_release.released_by = user.user_id "
  . "AND frs_package.group_id = frs_dlstats_grouptotal_agg.group_id "
  . "AND frs_release.status_id=1 ) "
  . "GROUP BY frs_release.release_id "
  . "ORDER BY frs_release.release_date DESC LIMIT ". $limit*3;

**/
$query = new_utils_get_new_releases_long(0,0,$limit*3);
$res=db_query($query);


// ## one time output
print " <channel>\n";
print "  <copyright>".$Language->getText('export_rss_sfnewreleases','copyright',array($GLOBALS['sys_long_org_name'],$GLOBALS['sys_name'],date('Y',time())))."</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>".$Language->getText('export_rss_sfnewreleases','new_releases',$GLOBALS['sys_name'])."</description>\n";
print "  <link>".get_server_url()."</link>\n";
print "  <title>".$Language->getText('export_rss_sfnewreleases','new_releases',$GLOBALS['sys_name'])."</title>\n";
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
print "  <webMaster>webmaster@".$host."</webMaster>\n";
print "  <language>en-us</language>\n";
// ## item outputs
$outputtotal = 0;
while ($row = db_fetch_array($res)) {
  if (!$G_RELEASE["$row[group_id]"]) {
    if ((!permission_exist("PACKAGE_READ",$row['package_id'] ))&&
	(!permission_exist("RELEASE_READ",$row['release_id'] ))) {
      print "  <item>\n";
      print "   <title>".htmlspecialchars($row['group_name']." ". $row['release_version'])."</title>\n";
      print "   <link>".get_server_url()."/file/showfiles.php?group_id=$row[group_id]</link>\n";
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
print " </channel>\n";
?>
</rss>
