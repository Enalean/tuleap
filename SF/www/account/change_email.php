<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

session_require(array(isloggedin=>1));

$LANG->loadLanguageMsg('account/account');

$HTML->header(array(title=> $LANG->getText('account_change_email', 'title')));
?>

<P><B><?php echo $LANG->getText('account_change_email', 'title'); ?></B>

<?php echo $LANG->getText('account_change_email', 'message'); ?>

<FORM action="change_email-confirm.php" method="post">
<P><INPUT type="hidden" name="form_user" value="<?php print user_getid(); ?>">
<?php echo $LANG->getText('account_change_email', 'label_new'); ?>:
<INPUT type="text" size="30" name="form_newemail">
<INPUT type="submit" name="Send Confirmation to New Address" value="<?php echo $LANG->getText('account_change_email', 'send_new'); ?>">
</FORM>

<P><A href="/">[<?php echo $LANG->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
