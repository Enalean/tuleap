<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
require "vars.php";
session_require(array('isloggedin'=>'1'));
require "account.php";

if ($group_id && $insert_license && $rand_hash && $form_license) {
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET license='$form_license', license_other='$form_license_other' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);
	if (db_affected_rows($result) < 1) {
		exit_error('Error','This is an invalid state. Update query failed. <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin']);
	}

} else {
	exit_error('Error','This is an invalid state. Some form variables were missing.
		If you are certain you entered everything, <B>PLEASE</B> report to '.$GLOBALS['sys_email_admin'].' and
		include info on your browser and platform configuration');
}

$HTML->header(array('title'=>'Project Category'));
?>

<H2>Step 6: Categories</H2>


<P><B>Project Categories</B>

<P>So that visitors to the site can find your project, you should select
categories that are most appropriate to your project's purpose.

<P>Your project will not be visible in the Trove software map until
(1) it is approved and (2) you have manually categorized your project in
your Project Administration page.

<P>After project approval, please immediately categorize your project
following the instructions in the email you will receive.

<FONT size=-1>
<FORM action="confirmation.php" method="post">
<INPUT TYPE="HIDDEN" NAME="show_confirm" VALUE="y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="rand_hash" VALUE="<?php echo $rand_hash; ?>">
<P>
<H2><FONT COLOR="RED">Do Not Back Arrow After This Point</FONT></H2> 
<P>
<INPUT type=submit name="Submit" value="Finish Registration">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>

