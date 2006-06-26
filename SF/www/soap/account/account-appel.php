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
$result = $client->call('login', array('loginname' => 'admin', 'passwd' => 'siteadmin'));
print_r($result);
$user_id = $result['user_id'];
$session_hash = $result['session_hash'];
echo '<H2>'.$user_id.'-------------'.$session_hash.'</H2>';
//$result = $client->call('getUserById', array('sessionKey' => $session_hash, 'user_id' => $user_id));
//$result = $client->call('getUserSkillInventory', array('sessionKey' => $session_hash, 'user_id' => $user_id));
//$result = $client->call('getTimezoneBox', array('sessionKey' => 'pop'));
$result = $client->call('getListOfGroupsByUser', array('sessionKey' => $session_hash, 'user_id' => $user_id));
//$client->call('logout', array('sessionKey' => $session_hash));
//$result = $client->call('getPeopleSkillBox', array('sessionKey' => $session_hash));
//$result = $client->call('getPeopleSkillBox');
//$result = $client->call('getPeopleSkillLevelBox');
//$result = $client->call('getPeopleSkillYearBox');.
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
