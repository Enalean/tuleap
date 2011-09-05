<?php

require_once('pre.php');

//define('TULEAP_WS_API_VERSION', '4.1');
define('CODENDI_WS_API_VERSION', '4.1');

define('LOG_SOAP_REQUESTS', false);

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$uri = $protocol.'://'.$sys_default_domain;

if ($request->exist('wsdl')) {
	header("Location: ".$uri."/plugins/tracker/soap/tuleap_tracker_v5.wsdl.php?wsdl");
	exit();
}
	
try {
	
    $server = new SoapServer($uri.'/plugins/tracker/soap/tuleap_tracker_v5.wsdl.php?wsdl',
    							array('trace' => 1, 
    								  'soap_version' => SOAP_1_1
    							));
    
    require_once(dirname(__FILE__).'/../../include/soap.php');
    
} catch (Exception $e) {
    echo $e;
}


// if POST was used to send this request, we handle it
// else, we display a list of available methods
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (LOG_SOAP_REQUESTS) {
        error_log('SOAP Request :');
        error_log($HTTP_RAW_POST_DATA);
    }
    $server -> handle();
} else {
	echo '<strong>This SOAP server can handle following functions : </strong>';    
    echo '<ul>';
    foreach($server -> getFunctions() as $func) {        
	    echo '<li>' , $func , '</li>';
	}
    echo '</ul>';
    echo '<a href="tuleap_tracker_v5.wsdl.php?wsdl">You can access the WSDL</a>';
}

?>
