<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_user'");
$row_user = db_fetch_array($res_user);

// send mail
$message = "Thank you for registering on the ".$GLOBALS['sys_name']." web site. In order\n"
	. "to complete your registration, visit the following url: \n\n"
	. "http://$GLOBALS[sys_default_domain]/account/verify.php?confirm_hash=$row_user[confirm_hash]\n\n"
	. "Enjoy the site.\n\n"
	. " -- The ".$GLOBALS['sys_name']." Team\n";


// only mail if pending
if ($row_user[status] == 'P') {
	mail($row_user[email], $GLOBALS['sys_name']." Account Registration",$message,"From: ".$GLOBALS['sys_email_admin']);
	$HTML->header(array(title=>"Account Pending Verification"));
?>

<P><B>Pending Account</B>

<P>Your email confirmation has been resent. Visit the link
in this email to complete the registration process.

<P><A href="/">[Return to <?php print $GLOBALS['sys_name']; ?>]</A>
 
<?php
} else {
	exit_error("Error","This account is not pending verification.");
}

$HTML->footer(array());

?>
