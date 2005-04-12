<?
require_once('pre.php');
require_once('common/wiki/WikiService.class');

$wiki = new WikiService($_REQUEST['group_id']);

$wiki->process();
?>