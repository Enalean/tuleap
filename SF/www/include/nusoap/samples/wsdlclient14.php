<?php
/*
 *	$Id: wsdlclient14.php,v 1.1 2005/04/22 19:16:08 snichol Exp $
 *
 *	WSDL client sample.
 *
 *	Service: WSDL
 *	Payload: rpc/encoded
 *	Transport: http
 *	Authentication: none
 */
require_once('../lib/nusoap.php');
require_once('../lib/class.wsdlcache.php');
$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';

echo 'You must set your own Via Michelin login and password in the source code to run this client!'; exit();
$login = '';
$password = '';

$wsdlurl = 'http://www.viamichelin.com/ws/services/Geocoding?wsdl';
$cache = new wsdlcache('.', 120);
$wsdl = $cache->get($wsdlurl);
if (is_null($wsdl)) {
	$wsdl = new wsdl($wsdlurl,
					$proxyhost, $proxyport, $proxyusername, $proxypassword);
	$cache->put($wsdl);
} else {
	$wsdl->debug_str = '';
	$wsdl->debug('Retrieved from cache');
}
$client = new soapclient($wsdl, true,
						$proxyhost, $proxyport, $proxyusername, $proxypassword);
$err = $client->getError();
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}

$inputAddresses[] = array(
	'address' => '45 Liberty Blvd.',
	'cityName' => 'Malvern',
	'countryCode' => 'USA',
	'postalCode' => '19355',
	'stateName' => 'PA'
);
$geocodingrequest = array('addressesList' => $inputAddresses);
$params = array('request' => $geocodingrequest, 'check' => "$login|$password");
$result = $client->call('getLocationsList', $params);

// Check for a fault
if ($client->fault) {
	echo '<h2>Fault</h2><pre>';
	print_r($result);
	echo '</pre>';
} else {
	// Check for errors
	$err = $client->getError();
	if ($err) {
		// Display the error
		echo '<h2>Error</h2><pre>' . $err . '</pre>';
	} else {
		// Display the result
		echo '<h2>Result</h2><pre>';
		print_r($result);
		echo '</pre>';
	}
}
echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
?>
