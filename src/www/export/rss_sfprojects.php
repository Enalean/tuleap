<?php
// ## export sf front page news in RSS
require_once('pre.php');
require('./rss_utils.inc');
require_once('www/new/new_utils.php');

$request = HTTPRequest::instance();
$server  = $request->getServerUrl();

if ($request->get('option') == "newest") {
  if (!$request->get('limit')) {
    $query = new_utils_get_new_projects(time(),0,10);
  } else {
    $query = new_utils_get_new_projects(time(),0,$request->get('limit'));
  }

  $res = db_query($query);
} else {
  $res = db_query("SELECT group_id,unix_group_name,group_name,short_description, xrx_export_ettm FROM groups '
		  .'WHERE access != '".db_es(Project::ACCESS_PRIVATE)."' AND status='A' AND type=1 ORDER BY group_id".($request->get('limit')?" LIMIT ".db_ei($request->get('limit')):""));
}

if ($request->get('type') == "rss") {

header("Content-Type: text/xml");
print '<?xml version="1.0"  encoding="UTF-8" ?>
<rss version="0.91">
';


// ## one time output
print " <channel>\n";
print "  <copyright>".$Language->getText('export_rss_sfnewreleases', 'copyright', array(
		ForgeConfig::get('sys_long_org_name'),
		ForgeConfig::get('sys_name'),
		date('Y',time())
	))."</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>". ForgeConfig::get('sys_name') ." ".$Language->getText('export_index','full_proj_listing')."</description>\n";
print "  <link>$server</link>\n";
if ($request->get('option') == "newest") {
	print "  <title>". ForgeConfig::get('sys_name') ." ".$Language->getText('export_rss_sfprojects','new_proj')."</title>\n";
} else {
	print "  <title>". ForgeConfig::get('sys_name') ." ".$Language->getText('export_index','full_proj_listing')."</title>\n";
}
print "  <webMaster>". ForgeConfig::get('sys_email_contact') ."</webMaster>\n";
print "  <language>en-us</language>\n";
// ## item outputs
while ($row = db_fetch_array($res)) {
	print "  <item>\n";
	print "   <title>".htmlspecialchars($row['group_name'])."</title>\n";
	print "   <link>$server/project/?group_id=". urlencode($row['group_id']) . "</link>\n";
	print "   <description>";
	print preg_replace("/ *\r*\n */"," ",rss_description($row['short_description']));
	print "</description>\n";
	print "  </item>\n";
}
// ## end output
print " </channel>\n";
 print "</rss>\n";

} else if ($request->get('type') == "csv") {

    header('Content-Type: text/csv');
    if ($request->get('option') == "newest") {
      header ('Content-Disposition: filename=new_projects.csv');
    } else {
      header ('Content-Disposition: filename=all_projects.csv');
    }

    // ## one time output. List of Exported fields
    print $Language->getText('export_rss_sfprojects','params')."\n";

    // ## item outputs
    while ($row = db_fetch_array($res)) {

	// Get languages, OS Runtime and Development state from trove map
	$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
		       .'trove_cat.fullpath_ids AS fullpath_ids,'
		       .'trove_cat.trove_cat_id AS trove_cat_id '
		       .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
		       .'trove_group_link.trove_cat_id AND trove_group_link.group_id='
		       .$row['group_id'].' ORDER BY trove_cat.fullpath');
	$lang = $os = $devstate = array();

	while ($row_trovecat = db_fetch_array($res_trovecat)) {
	    $folders = explode(" :: ",$row_trovecat['fullpath']);
	    $folders_len = count($folders);
	    
	    if ( preg_match("/".$Language->getText('export_rss_sfprojects','prog_lang')."/", $folders[0])) {
		$lang[] = $folders[$folders_len - 1];
	    }
	    else if ( preg_match("/".$Language->getText('export_rss_sfprojects','os')."/", $folders[0])) {
		$os[] = $folders[$folders_len - 1];
	    }
	    else if ( preg_match("/".$Language->getText('export_rss_sfprojects','devel_status')."/", $folders[0])) {
		$devstate[] = $folders[$folders_len - 1];
	    }
	    
	}

	// Get Project admin as contacts
	$res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname AS realname, user.email AS email "
			      . "FROM user,user_group "
			      . "WHERE user_group.user_id=user.user_id AND user_group.group_id=". db_ei($row['group_id']) ." AND "
			      . "user_group.admin_flags = 'A'");

	$admins = array();
	while ($row_admin = db_fetch_array($res_admin)) {
	    $admins[] = $row_admin['realname'].' <'.$row_admin['email'].'>';
	}
	
	print $row['group_id']. ",";
	print $row['unix_group_name'] . ",";
	print $row['group_name'] . ',';
	print '"'.preg_replace("/ *\r*\n */"," ",$row['short_description']).'",';
	print '"'.join(',',$lang).'",';
	print '"'.join(',',$os).'",';
	print '"'.join(',',$devstate).'",';
	
	print "$server/file/showfiles.php?group_id=" . $row['group_id'] . ",";
	print "$server/projects/". $row['unix_group_name'] . ",";
	print '"'.join(',',$admins).'",';
	
	// Finally print whether this file is visisble in the Xerox eTTM Software Inventory
	// Xerox specific
	print $row['xrx_export_ettm'];
	print "\n";
	
}
// ## end output
print "\n";
}
