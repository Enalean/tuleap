<?php
require_once ('pre.php');
require_once ('nusoap.php');

define ('permission_denied_fault', '3016');

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$uri = $protocol.'://'.$sys_default_domain;

// Instantiate server object
$server = new soap_server();
//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('CodeXTrackerAPI',$uri,false,'rpc','http://schemas.xmlsoap.org/soap/http',$uri);


//include the common TYPES API
//require_once('../common/types.php');

// include the TRACKER API
require_once('./tracker.php');


// Call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);


?>
