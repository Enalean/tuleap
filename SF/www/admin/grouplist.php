<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require($DOCUMENT_ROOT.'/admin/admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

site_admin_header(array('title'=>"Alexandria: Group List"));

// start from root if root not passed in
if (!$form_catroot) {
	$form_catroot = 1;
}

print "<br><a href=\"groupedit-add.php\">[Add Group]</a>";
print "<p>Alexandria Group List for Category: ";

if ($form_catroot == 1) {

	if (isset($group_name_search)) {
		print "<b>Groups that begin with $group_name_search</b>\n";
		$res = db_query("SELECT group_name,unix_group_name,group_id,is_public,status,license "
			. "FROM groups WHERE group_name LIKE '$group_name_search%' "
			. ($form_pending?"AND WHERE status='P' ":"")
			. " ORDER BY group_name");
	} else {
		print "<b>All Categories</b>\n";
		$res = db_query("SELECT group_name,unix_group_name,group_id,is_public,status,license "
			. "FROM groups "
			. ($status?"WHERE status='$status' ":"")
			. "ORDER BY group_name");
	}
} else {
	print "<b>" . category_fullname($form_catroot) . "</b>\n";

	$res = db_query("SELECT groups.group_name,groups.unix_group_name,groups.group_id,"
		. "groups.is_public,"
		. "groups.license,"
		. "groups.status "
		. "FROM groups,group_category "
		. "WHERE groups.group_id=group_category.group_id AND "
		. "group_category.category_id=$GLOBALS[form_catroot] ORDER BY groups.group_name");
}
?>

<P>
<TABLE width=100% border=1>
<TR>
<TD><b>Group Name (click to edit)</b></TD>
<TD><b>UNIX Name</b></TD>
<TD><b>Status</b></TD>
<TD><b>Public?</b></TD>
<TD><b>License</b></TD>
<TD><b>Categories</b></TD>
<TD><B>Members</B></TD>
</TR>

<?php
while ($grp = db_fetch_array($res)) {
	print "<tr>";
	print "<td><a href=\"groupedit.php?group_id=$grp[group_id]\">$grp[group_name]</a></td>";
	print "<td>$grp[unix_group_name]</td>";
	print "<td>$grp[status]</td>";
	print "<td>$grp[is_public]</td>";
	print "<td>$grp[license]</td>";
	
	// categories
	$count = db_query("SELECT group_id FROM group_category WHERE "
                . "group_id=$grp[group_id]");
        print ("<td>" . db_numrows($count) . "</td>");

	// members
	$res_count = db_query("SELECT user_id FROM user_group WHERE group_id=$grp[group_id]");
	print "<TD>" . db_numrows($res_count) . "</TD>";

	print "</tr>\n";
}
?>

</TABLE>

<?php
site_admin_footer(array());

?>
