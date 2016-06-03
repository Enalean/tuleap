<?php

require_once 'pre.php';

// Check if we the server is in secure mode or not.
if ($request->isSecure() || ForgeConfig::get('sys_force_ssl') == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}
$uri = $protocol.'://'.ForgeConfig::get('sys_default_domain');

$wsdl_renderer = new SOAP_WSDLRenderer();
$wsdl_renderer->render($uri."/soap/project/?wsdl");
