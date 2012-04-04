<?php

$client = new SoapClient('http://localhost:3080/soap/codendi.wsdl.php?wsdl');

// Disciplus is a restricted user
$session = $client->login('disciplus_1', 'Welcome0')->session_hash;

// Disciplus 2 is not member of a project disciplus_1 is member of
var_dump($client->checkUsersExistence($session, array('disciplus_2')));

$client->logout();

?>
