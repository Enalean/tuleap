<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
   
$LANG->loadLanguageMsg('account/account');

$HTML->header(array(title=>$LANG->getText('account_suspended', 'title')));
	
echo '<P>'.$LANG->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']));

echo $HTML->footer(array());

?>
