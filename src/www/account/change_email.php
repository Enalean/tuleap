<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('before_change_email', array());

session_require(array('isloggedin'=>1));

$Language->loadLanguageMsg('account/account');

$HTML->header(array('title'=> $Language->getText('account_change_email', 'title')));
?>

<P><B><?php echo $Language->getText('account_change_email', 'title'); ?></B>

<?php echo $Language->getText('account_change_email', 'message'); ?>

<FORM action="change_email-confirm.php" method="post">
<P><INPUT type="hidden" name="form_user" value="<?php print user_getid(); ?>">
<?php echo $Language->getText('account_change_email', 'label_new'); ?>:
<INPUT type="text" size="30" name="form_newemail">
<INPUT type="submit" name="Send Confirmation to New Address" value="<?php echo $Language->getText('account_change_email', 'send_new'); ?>">
</FORM>

<P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
