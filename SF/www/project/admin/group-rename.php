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

function register_valid()	{

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	if (!$GLOBALS['form_name']) {
		$GLOBALS['register_error'] = "You must supply a new name.";
		return 0;
	}

	group_add_history ('Changed Project Name','',$group_id);
	
	// if we got this far, it must be good
	db_query("UPDATE groups SET group_name='$GLOBALS[form_name]' WHERE group_id=" . $GLOBALS['group_id']); 
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/project/admin/?group_id=$group_id");
} else { // not valid registration, or first time to page
	project_admin_header(array('title'=>'Change Group Name','group'=>$group_id));

	?>
	<p><b>Group Name Change</b>
	<?php 

	if ($register_error) 
		print "<p>$register_error"; 

	?>
	<form action="group-rename.php" method="post">
	<p>New Group (Descriptive) Name:
	<br><input type="text" name="form_name">
	<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
	<p><input type="submit" name="Update" value="Update">
	</form>

	<?php
}
project_admin_footer(array());
?>
