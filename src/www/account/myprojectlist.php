<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
 
$Language->loadLanguageMsg('account/account');

$HTML->header(array(title=>$Language->getText('account_myprojectlist', 'title')));

$res_proj = db_query("SELECT groups.group_name AS group_name,"
		. "groups.group_id AS group_id,"
		. "groups.status AS status,"
		. "user_group.admin_flags AS admin_flags "
		. "FROM groups,user_group WHERE "
		. "groups.group_id=user_group.group_id AND "
		. "user_group.user_id=" . user_getid());
?>

<P><?php echo $Language->getText('account_myprojectlist', 'grp_list', array(user_getname())); ?></B>

<P>
<TABLE width=100% cellpadding=0 cellspacing=0 border=0 class="small">
<?php
while ($row_proj = db_fetch_array($res_proj)) {
	print "<TR>\n";
	print "<TD><A href=\"/project/?group_id=$row_proj[group_id]\">$row_proj[group_name]</A></TD>";
	print "</TR>\n";
}
?>
</TABLE>

<?php
$HTML->footer(array());

?>
