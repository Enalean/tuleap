<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

require($DOCUMENT_ROOT.'/include/pre.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>"Admin - Search Users/Groups"));

?>

<p>Administrative Functions

<p><B>User/Group/Category Maintenance</B>
<br>
<b> Search Criteria: </b> <?php print " \"%$search%\" <p>"; 

if ($search == "") {

  exit_error("Refusing to display whole DB","That would display whole DB.  Please use a CLI query if you wish to do this.");

}


if ($usersearch) {

	$sql = "select distinctrow * from user where user_id like '%$search%' or user_name like '%$search%' or email like '%$search%' or realname like '%$search%'";
	$result = db_query($sql) or exit_db(db_error());
	if (db_numrows($result) < 1) {
		print "No matches.<p><a href=\"/admin/\">Back</a>";

	}
	else {

		print "<table border=\"1\">";
		print "<tr><th>UserName</th><th>User's Name</th></tr>\n\n";

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

		print "No matches.<p><a href=\"/admin/\">Back</a>";

	}
	else {

		print "<table border=\"1\">";
		print "<tr><th>GroupUnixName</th><th>Group's Name</th></tr>\n\n";
		while ($row = db_fetch_array($result)) {
			print "<tr><td><a href=\"groupedit.php?group_id=$row[group_id]\">$row[unix_group_name]</a></td><td>$row[group_name]</td></tr>\n";
					
		}
		
		print "</table>";

	} 


} //end if($groupsearch)


$HTML->footer(array());
?>
