<?php


///////////////////////////////////////
// Configuration part
$test_server = 'http://' .$_SERVER['SERVER_ADDR'] .':'. $_SERVER['SERVER_PORT'];

$login = 'marcus';
$password = 'marcus';

$group_id = 101;
$tracker_id = 123;

$field_values = array(
        array(
            'artifact_id' => 0,
            'field_id' => 845,
            'field_label' => '',
            'field_value' => 868,
        ),
        array(
            'artifact_id' => 0,
            'field_id' => 847,
            'field_label' => '',
            'field_value' => '2009-12-05',
        ),
        array(
            'artifact_id' => 0,
            'field_id' => 846,
            'field_label' => '',
            'field_value' => 108,
        ),
        array(
            'artifact_id' => 0,
            'field_id' => 839,
            'field_label' => '',
            'field_value' => 'Write product backlog',
            ),
        array(
            'artifact_id' => 0,
            'field_id' => 840,
            'field_label' => '',
            'field_value' => 'Estimate it in story points, and prioritize it.',
            ),
        array(
            'artifact_id' => 0,
            'field_id' => 849,
            'field_label' => '',
            'field_value' => 13,
            ),
        array(
            'artifact_id' => 0,
            'field_id' => 850,
            'field_label' => '',
            'field_value' => 66.33,
            )
    );
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
    
    echo '<h1>Add an new artifact in tracker ' . $tracker_id . ' in project ' . $group_id . '</h1>';
    echo '<h3>function addArtifact</h3>';
    $art_id = $client->addArtifact($session_hash, $group_id, $tracker_id, $field_values);
    var_dump($art_id);
    
} catch (SoapFault $fault) {
    var_dump($fault);
}

?>
