<?php

require_once ('pre.php');
require_once ('nusoap.php');
require_once('utils_soap.php');

define ('permission_denied_fault', '3016');

define('NUSOAP', 1);

// Check if we the server is in secure mode or not.
$request = HTTPRequest::instance();
if ($request->isSecure() || ForgeConfig::get('sys_force_ssl') == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol.'://'.$default_domain;

// Instantiate server object
$server = new soap_server();

//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('CodendiAPI',$uri,false,'rpc','http://schemas.xmlsoap.org/soap/http',$uri);

//include the common TYPES API
require_once('./common/types.php');

//include the common SESSION API
require_once('./common/session.php');

// include the common GROUP API
require_once('./common/group.php');

// include the common USERS API
require_once('common/users.php');

// include the TRACKER API
require_once('./tracker/tracker.php');

// include the FRS API
require_once('./frs/frs.php');

// include the <Plugin> API (only if plugin is available), not tracker v5
$em =& EventManager::instance();
$em->processEvent('soap', array());

// Call the service method to initiate the transaction and send the response
$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
$server->service($HTTP_RAW_POST_DATA);

?>