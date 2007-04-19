<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: first.php 2724 2006-03-07 17:41:15Z guerin $

require_once('pre.php');    

$Language->loadLanguageMsg('account/account');

$HTML->header(array('title'=>$Language->getText('account_first', 'title', array($GLOBALS['sys_name']))));
?>

<P><h2><?php echo $Language->getText('account_first', 'title', array($GLOBALS['sys_name'])); ?></h2>

<P>
<?php 

$date = getdate(time());
$hoursleft = ($sys_crondelay - 1) - ($date['hours'] % $sys_crondelay);
$minutesleft = 60 - $date['minutes'];

echo $Language->getText('account_first', 'message', array($GLOBALS['sys_name'],$hoursleft,$minutesleft));

$HTML->footer(array());

?>
