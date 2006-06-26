<?php
/*
 *	$Id: wsdlclient2.php,v 1.2 2004/01/26 07:15:20 snichol Exp $
 *
 *	WSDL client sample.
 *
 *	Service: WSDL proxy
 *	Payload: rpc/encoded
 *	Transport: http
 *	Authentication: none
 */
require_once('../lib/nusoap.php');
$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
$client = new soapclient("http://soap.amazon.com/schemas2/AmazonWebServices.wsdl", true,
						$proxyhost, $proxyport, $proxyusername, $proxypassword);
$err = $client->getError();
if ($err) {
	echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
}
$proxy = $client->getProxy();
$param = array(
	'browse_node' => 18,
	'page' => 1,
	'mode' => 'books',
	'tag' =>'melonfire-20',
	'type' => 'lite',
	'devtag' => 'My token is here'
);
$result = $proxy->BrowseNodeSearchRequest($param);
// Check for a fault
if ($proxy->fault) {
	echo '<h2>Fault</h2><pre>';
	print_r($result);
	echo '</pre>';
} else {
	// Check for errors
	$err = $proxy->getError();
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
echo '<h2>Request</h2><pre>' . htmlspecialchars($proxy->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2><pre>' . htmlspecialchars($proxy->response, ENT_QUOTES) . '</pre>';
echo '<h2>Client Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
echo '<h2>Proxy Debug</h2><pre>' . htmlspecialchars($proxy->debug_str, ENT_QUOTES) . '</pre>';
?>
