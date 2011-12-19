<?php

require_once('pre.php');

define('CODENDI_WS_API_VERSION', '4.2');

define('LOG_SOAP_REQUESTS', false);

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$uri = $protocol.'://'.$sys_default_domain;

if ($request->exist('wsdl')) {
	header("Location: ".$uri."/soap/codendi.wsdl.php?wsdl");
	exit();
}
	
try {
	
    $server = new SoapServer($uri.'/soap/codendi.wsdl.php?wsdl',  
    							array('trace' => 1, 
    								  'soap_version' => SOAP_1_1
    							));

    require_once('utils_soap.php');
    require_once('common/session.php');
    require_once('common/group.php');
    require_once('common/users.php');
    require_once('tracker/tracker.php');
    require_once('frs/frs.php');
    
    // include the <Plugin> API (only if plugin is available)
	$em =& EventManager::instance();
	$em->processEvent('soap', array());
    
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
    echo '<a href="codendi.wsdl.php?wsdl">You can access the WSDL</a>';
}

?>
