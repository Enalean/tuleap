<?php

require_once 'pre.php';

$wsdl_renderer = new SOAP_WSDLRenderer();
$wsdl_renderer->render(HTTPRequest::instance()->getServerUrl().'/soap/index.php?wsdl');
