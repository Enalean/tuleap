<?
require_once('pre.php');
require_once('common/wiki/WikiServiceAdmin.class.php');

$Language->loadLanguageMsg('wiki/wiki');

$wiki = new WikiServiceAdmin($_REQUEST['group_id']);

$wiki->process();
?>
