<?php
// ## export sf front page news in RSS
include "pre.php";
include "rss_utils.inc";

$res = db_query('SELECT group_id,unix_group_name,group_name,homepage,short_description, xrx_export_ettm FROM groups '
	.'WHERE is_public=1 AND status=\'A\' ORDER BY group_id'.($limit?" LIMIT $limit":""));

if ($type == "rss") {

header("Content-Type: text/plain");
print '<?xml version="1.0"?>
<!DOCTYPE rss SYSTEM "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">
';

// ## one time output
print " <channel>\n";
print "  <copyright>Copyright (c) 2001-2002 Xerox, Inc.".$GLOBALS['sys_name']." Team. All Rights Reserved.</copyright>\n";
print "  <pubDate>".gmdate('D, d M Y g:i:s',time())." GMT</pubDate>\n";
print "  <description>Full Project Listing</description>\n";
print "  <link>http://$GLOBALS[sys_default_domain]</link>\n";
print "  <title>Full Project Listing</title>\n";
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
 print "</rss>\n";

} else if ($type == "csv") {

    header('Content-Type: text/csv');
    header ('Content-Disposition: filename=all_projects.csv');

    // ## one time output. List of Exported fields
    print 'Project ID, Short Name, Name, Description, Language, OS Runtime Support,'.
	'Development State, Release Location, Additional Information, Point of contact,'.
	'eTTM Inventory'."\n";

    // ## item outputs
    while ($row = db_fetch_array($res)) {

	// Get languages, OS Runtime and Development state from trove map
	$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
		       .'trove_cat.fullpath_ids AS fullpath_ids,'
		       .'trove_cat.trove_cat_id AS trove_cat_id '
		       .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
		       .'trove_group_link.trove_cat_id AND trove_group_link.group_id='
		       .$row[group_id].' ORDER BY trove_cat.fullpath');
	$lang = $os = $devstate = array();

	while ($row_trovecat = db_fetch_array($res_trovecat)) {
	    $folders = explode(" :: ",$row_trovecat['fullpath']);
	    $folders_len = count($folders);
	    
	    if ( preg_match("/Programming Language/", $folders[0])) {
		$lang[] = $folders[$folders_len - 1];
	    }
	    else if ( preg_match("/Operating System/", $folders[0])) {
		$os[] = $folders[$folders_len - 1];
	    }
	    else if ( preg_match("/Development Status/", $folders[0])) {
		$devstate[] = $folders[$folders_len - 1];
	    }
	    
	}

	// Get Project admin as contacts
	$res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname AS realname, user.email AS email "
			      . "FROM user,user_group "
			      . "WHERE user_group.user_id=user.user_id AND user_group.group_id=".$row[group_id]." AND "
			      . "user_group.admin_flags = 'A'");

	$admins = array();
	while ($row_admin = db_fetch_array($res_admin)) {
	    $admins[] = $row_admin['realname'].' <'.$row_admin['email'].'>';
	}
	
	print "$row[group_id],";
	print "$row[unix_group_name],";
	print "\"$row[group_name]\",";
	print '"'.ereg_replace(" *\r*\n *"," ",$row['short_description']).'",';
	print '"'.join(',',$lang).'",';
	print '"'.join(',',$os).'",';
	print '"'.join(',',$devstate).'",';
	
	print "http://$GLOBALS[sys_default_domain]/project/showfiles.php?group_id=$row[group_id],";
	print "http://$GLOBALS[sys_default_domain]/project/$row[unix_group_name],";
	print '"'.join(',',$admins).'",';
	
	// Finally print whether this file is visisble in the Xerox eTTM Software Inventory
	// Xerox specific
	print "$row[xrx_export_ettm]";
	print "\n";
	
}
// ## end output
print "\n";
}
?>
