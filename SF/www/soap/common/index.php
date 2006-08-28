<?php
require_once ('pre.php');
require_once ('nusoap/lib/nusoap.php');

define ('permission_denied_fault', '3016');

$uri = 'http://'.$sys_default_domain;

// Instantiate server object
$server = new soap_server();
//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('CodeXCommonAPI',$uri,false,'rpc','http://schemas.xmlsoap.org/soap/http',$uri);

//include the common TYPES API
require_once('./types.php');

//include the common SESSION API
require_once('./session.php');

// include the common USER API
require_once('./user.php');

// include the common GROUP API
require_once('./group.php');

// Call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);


?>
