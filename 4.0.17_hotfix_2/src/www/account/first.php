<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    


$HTML->header(array('title'=>$Language->getText('account_first', 'title', array($GLOBALS['sys_name']))));
?>

<P><h2><?php echo $Language->getText('account_first', 'title', array($GLOBALS['sys_name'])); ?></h2>

<P>
<?php 

echo $Language->getText('account_first', 'message', array($GLOBALS['sys_name']));

$HTML->footer(array());

?>
