<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	if (!$GLOBALS[form_realname]) {
		$GLOBALS[register_error] = "You must supply a new real name.";
		return 0;
	}
	
	// if we got this far, it must be good
	db_query("UPDATE user SET realname='$GLOBALS[form_realname]' WHERE user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change RealName"));

?>
<p><b>RealName Change</b>
<?php if ($register_error) print "<p>$register_error"; ?>
<form action="change_realname.php" method="post">
<p>New Real Name:
<br><input type="text" name="form_realname">
<p><input type="submit" name="Update" value="Update">
</form>

<?php
}
$HTML->footer(array());

?>
