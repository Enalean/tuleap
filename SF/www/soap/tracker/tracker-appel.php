<?php
// Pull in the NuSOAP code
require_once ('nusoap/lib/nusoap.php');

// Http Authentication
define ('username', 'test');
define ('password', 'test');

// Create the client instance
//$client = new soapclient('http://' . username . ':' . password . '@esparros.grenoble.xrce.xerox.com:8000/soap/account/account-service.php?wsdl', true);
//$client->setCredentials(username, password);
$client = new soapclient('http://esparros.grenoble.xrce.xerox.com:8000/soap/account/account-service.php?wsdl', true);
// Check for an error
$err = $client->getError();
if ($err) {
    // Display the error
    echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
    // At this point, you know the call that follows will fail
}
// Call the SOAP method
$result = $client->call('login', array('loginname' => 'moubouho', 'passwd' => '2pacmail'));
print_r($result);
$user_id = $result['user_id'];
$session_hash = $result['session_hash'];
echo '<H2>Session : '.$user_id.'-------------'.$session_hash.'</H2>';

$client = new soapclient('http://esparros.grenoble.xrce.xerox.com:8000/soap/tracker/tracker-service.php?wsdl', true, 'cornillon.grenoble.xrce.xerox.com', '8000');

//$result = $client->call('getArtifactTypes', array('sessionKey' => $session_hash, 'group_id' => 1, 'user_id' => 131));
$group_id = 1;
$group_artifact_id = 410;
$user_id = 131;
$result = $client->call('getFieldSets', array('sessionKey' => $session_hash, 'group_id' => $group_id, 'group_artifact_id' => $group_artifact_id));
/*
$criteria = array();
$criteria[] = array ('field_name' => 'assigned_to' , 'field_value' => '132');
$criteria[] = array ('field_name' => 'status_id' , 'field_value' => '1');
$criteria[] = array ('field_name' => 'open_date' , 'field_value' => '1145366966', 'operator' => '=');

$result = $client->call('getArtifacts', array('sessionKey' => $session_hash, 'group_id' => $group_id, 'group_artifact_id' => 410, 'user_id' => $user_id, 'criteria' => $criteria ));
*/
// test d'insertion d'un artifact

$extra_fields = array();
//$extra_fields[] = array ('field_id' => 9 , 'field_value' => 132);
//$extra_fields[] = array ('field_id' => 182 , 'field_value' => '102,103' );
$extra_fields[] = array ('field_id' => 181 , 'field_value' => 'test de mise a jour artifact' );
//$result = $client->call('addArtifact', array('sessionKey' => $session_hash, 'group_id' => $group_id, 'group_artifact_id' => $group_artifact_id, 'user_id' => $user_id, 'severity' => 4, 'summary' => 'ceci est un  test pour abbadi 2', 'extra_fields' => $extra_fields));

//$result = $client->call('updateArtifact', array('sessionKey' => $session_hash, 'group_id' => $group_id, 'group_artifact_id' => $group_artifact_id, 'user_id' => $user_id, 'artifact_id' => 6974, 'severity' => 4));

//$result = $client->call('getArtifactCannedResponses', array('sessionKey' => $session_hash, 'group_id' => $group_id, 'group_artifact_id' => 409));
//$result = $client->call('getArtifactTypes', array('sessionKey' => $session_hash, 'group_id' => 107));

//$result = $client->call('getArtifactFollowups', array('sessionKey' => $session_hash, 'group_id' => 107, 'group_artifact_id' => 161, 'artifact_id' => 67));
/*
$result = $client->call('getArtifactReports', array('sessionKey' => $session_hash, 'group_id' => 107, 'group_artifact_id' => 125, 'user_id' => 101));
*/
$ids = array();
$ids[] = 103;
$ids[] = 76;
//$result = $client->call('getDependancies', array('sessionKey' => $session_hash, 'group_id' => 1, 'group_artifact_id' => 410, 'artifact_id' => 6954));
/*$result = $client->call('existSummary', array('sessionKey' => $session_hash, 'group_artifact_id' => 410, 'summary' => '3333'));
echo '<H2>POP'.$result.'</H2>';
if ($result)
	echo '<H2>FOUND</H2>';
else 
	echo '<H2>NOT FOUND</H2>';*/
	
//$result = $client->call('addFollowup', array('sessionKey' => $session_hash, 'group_id' => 1, 'group_artifact_id' => 410, 'artifact_id' => 6954, 'body' => 'test followup soap 1'));
//$result = $client->call('deleteDependency', array('sessionKey' => $session_hash, 'group_id' => 1, 'group_artifact_id' => 410, 'artifact_id' => 6954, 'dependent_on_artifact_id' => 3));
/*
$is_dependent_on_artifact_id = array(6955, 7010);
$result = $client->call('addDependencies', array('sessionKey' => $session_hash, 'group_id' => 1, 'group_artifact_id' => 410, 'artifact_id' => 6954, 'is_dependent_on_artifact_id' => $is_dependent_on_artifact_id));
/*
$result = $client->call('getAttachedFiles', array('sessionKey' => $session_hash, 'group_id' => 107, 'group_artifact_id' => 126, 'artifact_id' => 103));

for ($i=0;$i<count($result);$i++) {
	$file = $result[$i];
	$filename = $file['filename'];
	$submitted_by = $file['submitted_by'];
	echo "---------------filename : ".$filename." <br/>";
	echo "---------------submitted by : ".$submitted_by." <br/>";
	$bin_data = $file['bin_data'];
	if (!($f = @fopen($filename, "wb"))) {
	  	 echo "Couldn't open file ".$filename." for writing<br/>";
	} else {
	 	 fwrite($f, $bin_data, strlen($bin_data));
	  	 fclose($f);
	  	 echo "File Retrieved Successfully<br/>";
	}
}
*/
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
// Display the request and response
echo '<h2>Request</h2>';
echo '<pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
echo '<h2>Response</h2>';
echo '<pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
// Display the debug messages

echo '<h2>Debug</h2>';
echo '<pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
?>
