<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

$Language->loadLanguageMsg('account/account');

if ($GLOBALS['sys_auth_type'] == 'ldap') {
    // Don't send LDAP password!
    // There should be no link to this page...
    exit_permission_denied();
}

$HTML->header(array('title'=>$Language->getText('account_lostpw', 'title')));
?>

<P><?php echo $Language->getText('account_lostpw', 'message'); ?>

<FORM action="lostpw-confirm.php" method="post">
<P><INPUT type="hidden" name="form_user" value="<?php print $form_user; ?>">
Login Name:
<INPUT type="text" name="form_loginname">
<INPUT type="submit" name="Send Lost Password Hash" value="<?php echo $Language->getText('account_lostpw', 'send_hash'); ?>">
</FORM>

<P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
