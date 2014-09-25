<?php

require_once 'pre.php';

$src = dirname(dirname($_SERVER['SCRIPT_URI']))."/soap/index.php?wsdl";

$wsdl_renderer = new SOAP_WSDLRenderer();
$wsdl_renderer->render($src);
