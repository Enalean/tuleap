<?php
/*
 *	$Id: wsdlclient12.php,v 1.3 2005/01/24 21:58:48 snichol Exp $
 *
 *	WSDL client sample.
 *
 *	Service: WSDL
 *	Payload: document/literal
 *	Transport: http
 *	Authentication: none
 */
require_once('../lib/nusoap.php');
require_once('../lib/class.wsdlcache.php');
$proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
$proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
$proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
$proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';

$method = isset($_GET['method']) ? $_GET['method'] : 'ItemSearch';

echo 'You must set your own Amazon E-Commerce Services subscription id in the source code to run this client!'; exit();
$SubscriptionId = 'Your AWS subscription id';

$wsdlurl = 'http://webservices.amazon.com/AWSECommerceService/US/AWSECommerceService.wsdl';
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

$client->soap_defencoding = 'UTF-8';

function GetCartCreateParams() {
	global $SubscriptionId;

	// create items to be added to the cart
	$item = array ();
	$item[0] = array(  "ASIN" => "0596004206",
					   "Quantity" => "1"
					);
	$item[1] = array(  "ASIN" => "0596003277",
					   "Quantity" => "2"
					);

	// pack it to <Item> array
	$items =  array("Item" => $item);
	// Construct request parameters
	$request = array("Items" => $items, "ResponseGroup" => "CartSimilarities");
	
	// Construct  all parameters
	$cartCreate = array(	"SubscriptionId"  => $SubscriptionId,
							"Request" => $request
					 	);

	return $cartCreate;
}

function GetItemSearchParams() {
	global $SubscriptionId;

	$itemSearchRequest = array(
		'BrowseNode' => '53',
		'ItemPage' => 1,
	//	'ResponseGroup' => array('Request', 'Small'),
		'SearchIndex' => 'Books',
		'Sort' => 'salesrank'
	);
	
	$itemSearch = array(
		'SubscriptionId' => $SubscriptionId,
	//	'AssociateTag' => '',
	//	'Validate' => '',
	//	'XMLEscaping' => '',
	//	'Shared' => $itemSearchRequest,
		'Request' => array($itemSearchRequest)
	);
	
	return $itemSearch;
}

function GetItemSearchParams2() {
	global $SubscriptionId;

	$request = array(
		"Keywords" => "postal stamps",
		"SearchIndex" => "Books"
	);

	$itemSearch = array(
		'SubscriptionId' => $SubscriptionId,
		'Request' => $request
	);

	return $itemSearch;
}

if ($method == 'ItemSearch') {
	$result = $client->call('ItemSearch', array('body' => GetItemSearchParams()));
} elseif ($method == 'ItemSearch2') {
	$result = $client->call('ItemSearch', array('body' => GetItemSearchParams2()));
} elseif ($method == 'CartCreate') {
	$result = $client->call('CartCreate', array('body' => GetCartCreateParams()));
} else {
	echo "Unsupported method $method";
	exit;
}

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
