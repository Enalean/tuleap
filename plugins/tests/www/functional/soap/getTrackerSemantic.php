<?php

///////////////////////////////////////
// Configuration part
$_SERVER['SERVER_ADDR'] = 'sonde.cro.enalean.com';
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] /*.':'. $_SERVER['SERVER_PORT']*/;

$login = 'admin';
$password = 'siteadmin';

$group_id   = 117;
$tracker_id = 8;
///////////////////////////////////////

try {
    
    $client_tracker_v5 = new SoapClient($test_server.'/plugins/tracker/soap/?wsdl',
                                array(//'trace' => true,
                                      'trace'      => 1,
                                      'exceptions' => 0,
                                      'soap_version' => SOAP_1_1,
                                      'cache_wsdl' => 0,
                                      //'proxy_host' => 'localhost',
                                      //'proxy_port' => 8008
                                ));
    
    $client = new SoapClient($test_server.'/soap/codendi.wsdl.php?wsdl',
                                array(//'trace' => true,
                                      'trace'      => 1,
                                      'exceptions' => 0,
                                      'soap_version' => SOAP_1_1,
                                      'cache_wsdl' => 0, 
                                      //'proxy_host' => 'localhost', 
                                      //'proxy_port' => 8008
                                ));
    
    $session =  $client->login($login, $password);

    $session_hash = (string)$session->session_hash;
    $user_id = $session->user_id;
    
    echo 'User ' . $login . ' (user_id=' . $user_id . ') is logged with session hash = ' .$session_hash . '<br>';
    
    echo '<h1>Get semantic of tracker ' . $tracker_id . '</h1>';
    echo '<h3>function getTrackerSemantic</h3>';
    $trackerlist = $client_tracker_v5->getTrackerSemantic($session_hash, $group_id, $tracker_id);
    var_dump($trackerlist);
    
} catch (SoapFault $fault) {
    var_dump($fault);
}

?>
