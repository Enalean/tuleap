<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function submit_valid()
// ###### checks for valid submit from form post

function submit_valid()	{
	global $HTTP_POST_VARS;

	if (!$HTTP_POST_VARS["Submit"]) {
		return 0;
	}
	
	if ($HTTP_POST_VARS['form_groupname']) {
		db_query("INSERT INTO groups (group_name,is_public) "
			. "values ('$HTTP_POST_VARS[form_groupname]',$HTTP_POST_VARS[form_public])");
		return 1;
	} else {
		return 0;
	}
}

// ###### first check for valid login, if so, congratulate

if (submit_valid()) {
	session_redirect("/admin/index.php");
} else { // not valid registration, or first time to page
	$HTML->header(array('title'=>"Admin - New Group"));

?>
<p><b>New Group (Project) Creation</b>
<?php if ($submit_error) print "<p>$submit_error"; ?>
<form action="newgroup.php" method="post">
<p>Group Name:
<br><input type="text" name="form_groupname">
<p>Publicly browseable?:
<br><SELECT name="form_public">
<OPTION value="1">Yes
<OPTION selected value="0">No
</SELECT>
<p><input type="submit" name="Submit" value="Submit">
</form>

<?php
}
$HTML->footer(array());

?>
