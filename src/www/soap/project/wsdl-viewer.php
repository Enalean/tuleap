<?php
$xslDoc = new DOMDocument();
$xslDoc->load("../wsdl-viewer.xsl");

$xmlDoc = new DOMDocument();
//$src = dirname(dirname($_SERVER['SCRIPT_URI']))."/soap/index.php?wsdl";
$src = "http://shunt.cro.enalean.com/soap/project/?wsdl";
$xml = file_get_contents($src);
$xmlDoc->loadXML($xml);

$proc = new XSLTProcessor();
$proc->importStylesheet($xslDoc);
echo $proc->transformToXML($xmlDoc);

?>
