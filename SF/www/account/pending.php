<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
$HTML->header(array(title=>"Suspended Account"));
?>

<P><B>Pending Account</B>

<P>Your account is currently pending your email confirmation.
Visiting the link sent to you in this email will activate your account.

<P>If you need this email resent, please click below and a confirmation
email will be sent to the email address you provided in registration.

<P><A href="pending-resend.php?form_user=<?php print $form_user; ?>">[Resend Confirmation Email]</A>
<BR><A href="/">[Return to SourceForge]</A>
 
<?php
$HTML->footer(array());

?>
