<?php


///////////////////////////////////////
// Configuration part
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] .':'. $_SERVER['SERVER_PORT'];

$login = 'marcus';
$password = 'marcus';

$group_id = 101;
$tracker_id = 123;
$artifact_id = 82;

$field_values = array(
        array(
            'artifact_id' => 82,
            'field_id' => 839,
            'field_label' => '',
            'field_value' => 'Do stuff dude!!!!!!',
        )
    );
$follow_up_comment = 'Updated some fields from SOAP API';
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
    
    echo '<h1>Update artifact # ' . $artifact_id . ' of tracker ' . $tracker_id . ' in project ' . $group_id . '</h1>';
    echo '<h3>function updateArtifact</h3>';
    $ok = $client->updateArtifact($session_hash, $group_id, $tracker_id, $artifact_id, $field_values, $follow_up_comment);
    if ($ok) {
        var_dump("Artifact updated.");
    } else {
        var_dump("Artifact not updated: Error.");
    }
    
} catch (SoapFault $fault) {
    var_dump($fault);
}

?>
