<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php'); 
   
$LANG->loadLanguageMsg('account/account');

$HTML->header(array(title=>$LANG->getText('account_pending', 'title')));
?>

<P><?php echo $LANG->getText('account_pending', 'message'); ?>

<P><A href="pending-resend.php?form_user=<?php print $form_user; ?>">[<?php echo $LANG->getText('account_pending', 'btn_resend'); ?>]</A>
<BR><A href="/">[<?php echo $LANG->getText('global', 'back_home'); ?>]</A>
 
<?php
$HTML->footer(array());

?>
