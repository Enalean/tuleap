<?php

require_once 'pre.php';

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || Config::get('sys_force_ssl') == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}
$uri = $protocol.'://'.Config::get('sys_default_domain');

$proc = new XSLTProcessor();

$xslDoc = new DOMDocument();
$xslDoc->load("../wsdl-viewer.xsl");
$proc->importStylesheet($xslDoc);

$xmlDoc = new DOMDocument();
$xmlDoc->loadXML(file_get_contents($uri."/soap/project/?wsdl"));
echo $proc->transformToXML($xmlDoc);

?>
