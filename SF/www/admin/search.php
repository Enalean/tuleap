<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');

$LANG->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

if ($search == "") {
  exit_error("ERROR",$LANG->getText('admin_search','error_nowholedb'));
}

$HTML->header(array('title'=>$LANG->getText('admin_search','title')));

?>

<h2><?php echo $LANG->getText('admin_search','header'); ?></h2>

<p><h3><?php echo $LANG->getText('admin_search','maintenance'); ?></h3>
<br>
<b> <?php echo $LANG->getText('admin_search','criteria'); ?>: </b> <?php print " \"%$search%\" <p>"; 


if ($usersearch) {

	$sql = "select distinctrow * from user where user_id like '%$search%' or user_name like '%$search%' or email like '%$search%' or realname like '%$search%'";
	$result = db_query($sql) or exit_db(db_error());
	if (db_numrows($result) < 1) {
	    print $LANG->getText('admin_search','nomatch').".<p><a href=\"/admin/\">".$LANG->getText('global','back')."</a>";

	}
	else {

		print "<table border=\"1\">";
		print "<tr><th>".$LANG->getText('admin_search','login')."</th><th>".$LANG->getText('admin_search','user_name')."</th></tr>\n\n";

		while ($row = db_fetch_array($result)) {
			print "<tr><td><a href=\"usergroup.php?user_id=$row[user_id]\">$row[user_name]</a></td><td>$row[realname]</td></tr>\n"; 
		}
		print "</table>";

	} 
} // end if ($usersearch)


if ($groupsearch) {

	$sql = "select distinctrow * from groups where group_id like '%$search%' or unix_group_name like '%$search%' or group_name like '%$search%'";
	$result = db_query($sql) or exit_db(db_error());

	if (db_numrows($result) < 1) {

	    print $LANG->getText('admin_search','nomatch').".<p><a href=\"/admin/\">".$LANG->getText('global','back')."</a>";

	}
	else {

		print "<table border=\"1\">";
		print "<tr><th>".$LANG->getText('admin_search','unix_grp_name')."</th><th>".$LANG->getText('admin_search','grp_name')."</th></tr>\n\n";
		while ($row = db_fetch_array($result)) {
			print "<tr><td><a href=\"groupedit.php?group_id=$row[group_id]\">$row[unix_group_name]</a></td><td>$row[group_name]</td></tr>\n";
					
		}
		
		print "</table>";

	} 


} //end if($groupsearch)


$HTML->footer(array());
?>
