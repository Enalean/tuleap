<?php
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
require_once __DIR__ . '/../include/pre.php';


$HTML->header(array('title' => $Language->getText('account_deleted', 'title')));
list($host,$port) = explode(':', $GLOBALS['sys_default_domain']);
?>

<P><B><?php echo $Language->getText('account_deleted', 'title'); ?></B>

<P><?php echo $Language->getText('account_deleted', 'message', array($GLOBALS['sys_email_contact'], $GLOBALS['sys_email_contact'])); ?>

<?php
$HTML->footer(array());

?>
