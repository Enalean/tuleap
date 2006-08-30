<?php
require_once ('pre.php');
require_once ('nusoap/lib/nusoap.php');

define ('permission_denied_fault', '3016');

$uri = 'http://'.$sys_default_domain;

// Instantiate server object
$server = new soap_server();
//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('CodeXAccountAPI',$uri,false,'rpc','http://schemas.xmlsoap.org/soap/http',$uri);


// include the ACCOUNT API
require_once('./account.php');


// Call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);


?>
