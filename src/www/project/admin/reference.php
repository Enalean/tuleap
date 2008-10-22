<?
require_once('pre.php');
require_once('common/include/HTTPRequest.class.php');
require_once('./include/ReferenceAdministration.class.php');


$refAdmin = new ReferenceAdministration();

$refAdmin->process();
?>
