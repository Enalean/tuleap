<?
require_once('pre.php');
require_once('common/include/HTTPRequest.class');
require_once('./include/ReferenceAdministration.class');

$Language->loadLanguageMsg('include/reference');

$refAdmin = new ReferenceAdministration();

$refAdmin->process();
?>
