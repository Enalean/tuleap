<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');

$Language->loadLanguageMsg('account/account');

$HTML->header(array('title'=>$Language->getText('account_pending', 'title')));

$request =& HTTPRequest::instance();
$purifier =& CodeX_HTMLPurifier::instance();

$form_user = $purifier->purify($request->get('form_user'));
?>

<P><?php echo $Language->getText('account_pending', 'message'); ?>

<P><A href="pending-resend.php?form_user=<?php echo $form_user; ?>">[<?php echo $Language->getText('account_pending', 'btn_resend'); ?>]</A>
<BR><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
