<?php


///////////////////////////////////////
// Configuration part
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] .':'. $_SERVER['SERVER_PORT'];

$login = 'marcus';
$password = 'marcus';

$group_id = 101;
$tracker_id = 102;
///////////////////////////////////////

try {
    
    $client = new SoapClient($test_server.'/soap/codendi.wsdl.php?wsdl', 
                                array(//'trace' => true,
                                      'trace'      => 1,
                                      'exceptions' => 0,
                                      'soap_version' => SOAP_1_1,
                                      //'proxy_host' => 'localhost', 
                                      //'proxy_port' => 8008
                                ));
    
    $session =  $client->login($login, $password);
    
    $session_hash = $session->session_hash;
    $user_id = $session->user_id;
    
    echo 'User ' . $login . ' (user_id=' . $user_id . ') is logged with session hash = ' .$session_hash . '<br>';
    
    echo '<h1>Get artifacts of tracker ' . $tracker_id . ' in project ' . $group_id . '</h1>';
    echo '<h3>function getArtifacts</h3>';
    $artifacts = $client->getArtifacts($session_hash, $group_id, $tracker_id);
    var_dump($artifacts);
    
} catch (SoapFault $fault) {
    var_dump($fault);
}

?>
