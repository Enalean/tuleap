<?
require_once('pre.php');
require_once('common/wiki/WikiService.class');

$Language->loadLanguageMsg('wiki/wiki');

$wiki = new WikiService($_REQUEST['group_id']);

$wiki->process();
?>