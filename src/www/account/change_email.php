<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/include/CSRFSynchronizerToken.class.php');
require_once('common/event/EventManager.class.php');

$em =& EventManager::instance();
$em->processEvent('before_change_email', array());

session_require(array('isloggedin'=>1));


$HTML->header(array('title'=> $Language->getText('account_change_email', 'title')));
$csrf = new CSRFSynchronizerToken('/account/change_email.php');
?>

<h2><?php echo $Language->getText('account_change_email', 'title'); ?></h2>

<?php echo $Language->getText('account_change_email', 'message'); ?>

<FORM action="change_email-confirm.php" method="post" class="form-inline">
<?php
echo $csrf->fetchHTMLInput();
?>
<INPUT type="hidden" name="form_user" value="<?php print user_getid(); ?>">
<BR>
<?php echo $Language->getText('account_change_email', 'label_new'); ?>:
<INPUT type="text" size="30" name="form_newemail">
<INPUT class="btn btn-primary" type="submit" name="Send Confirmation to New Address" value="<?php echo $Language->getText('account_change_email', 'send_new'); ?>">
</FORM>

<A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
