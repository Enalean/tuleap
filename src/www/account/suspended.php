<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: suspended.php 1405 2005-03-21 14:41:41Z guerin $

require_once('pre.php');
   
$Language->loadLanguageMsg('account/account');

$HTML->header(array(title=>$Language->getText('account_suspended', 'title')));
	
echo '<P>'.$Language->getText('account_suspended', 'message', array($GLOBALS['sys_email_contact']));

echo $HTML->footer(array());

?>
