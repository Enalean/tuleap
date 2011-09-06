<?php


///////////////////////////////////////
// Configuration part
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] /*.':'. $_SERVER['SERVER_PORT']*/;

$login = 'sandrae';
$password = 'sandrae';

$group_id = 112;
///////////////////////////////////////

try {
    
    $client_tracker_v5 = new SoapClient($test_server.'/plugins/tracker/soap/wsdl?wsdl',
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
    
    echo '<h1>Get list of trackers in project ' . $group_id . '</h1>';
    echo '<h3>function getTrackerList</h3>';
    $trackerlist = $client_tracker_v5->getTrackerList($session_hash, $group_id);
    var_dump($trackerlist);
    
} catch (SoapFault $fault) {
    var_dump($fault);
}
