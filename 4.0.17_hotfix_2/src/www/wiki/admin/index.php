<?
require_once('pre.php');
require_once('common/wiki/WikiServiceAdmin.class.php');


$wiki = new WikiServiceAdmin($_REQUEST['group_id']);

$wiki->process();
?>
