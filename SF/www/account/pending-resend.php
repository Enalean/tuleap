<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/proj_email.php');

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_user'");
$row_user = db_fetch_array($res_user);

// only mail if pending
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
if ($row_user[status] == 'P') {
    send_new_user_email($row_user['email'], $row_user['confirm_hash']);
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
