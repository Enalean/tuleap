<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/admin/admin_utils.php');

$Language->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

site_admin_header(array('title'=>$Language->getText('admin_grouplist','title')));

// start from root if root not passed in
if (!$form_catroot) {
	$form_catroot = 1;
}

print "<br><a href=\"groupedit-add.php\">[".$Language->getText('admin_grouplist','add_group')."]</a>";
print "<p>".$Language->getText('admin_grouplist','for_categ').": ";

if ($form_catroot == 1) {

	if (isset($group_name_search)) {
	    print "<b>".$Language->getText('admin_grouplist','begins_with',array($group_name_search))."</b>\n";
		$res = db_query("SELECT group_name,unix_group_name,group_id,is_public,status,license "
			. "FROM groups WHERE group_name LIKE '$group_name_search%' "
			. ($form_pending?"AND WHERE status='P' ":"")
			. " ORDER BY group_name");
	} else {
	    print "<b>".$Language->getText('admin_grouplist','all_categ')."</b>\n";
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
<TD><b><?php echo $Language->getText('admin_groupedit','grp_name')." ".$Language->getText('admin_grouplist','click');?></b></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','unix_grp'); ?></b></TD>
<TD><b><?php echo $Language->getText('global','status'); ?></b></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','public'); ?></b></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','license'); ?></b></TD>
<TD><b><?php echo $Language->getText('admin_grouplist','categ'); ?></b></TD>
<TD><B><?php echo $Language->getText('admin_grouplist','members'); ?></B></TD>
</TR>

<?php
$odd_even = array('boxitem', 'boxitemalt');
$i = 0;
while ($grp = db_fetch_array($res)) {
	print "<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">";
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
