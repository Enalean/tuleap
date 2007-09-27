<?
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('./include/ReferenceAdministration.class.php');

$Language->loadLanguageMsg('project/project');

$refAdmin = new ReferenceAdministration();

$refAdmin->process();
?>
