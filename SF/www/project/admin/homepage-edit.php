<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
require ($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($GLOBALS[Submit]) {
	if ($form_homepage) {
		db_query("UPDATE groups SET homepage='$form_homepage' "
			. "WHERE group_id=$group_id");	
		session_redirect("/project/admin/?group_id=$group_id");
	}
}

$res_grp = db_query("SELECT homepage FROM groups WHERE group_id=$group_id");
$row_grp = db_fetch_array($res_grp); 

project_admin_header(array('title'=>'Edit Homepage URL','group'=>$group_id));
?>
<P>Editing URL for project: <B><?php html_a_group($group_id); ?></B>

<P><FORM action="homepage-edit.php" method="post">
New URL:
<BR><I>Without "http://". Example "myproject.<?php echo $GLOBALS['sys_default_domain']; ?>".</I>
<BR><INPUT type="text" name="form_homepage" value="<?php print $row_grp[homepage]; ?>">
<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><INPUT type="submit" name="Submit" value="Submit">
</FORM>

<?php
project_admin_footer(array());
?>
