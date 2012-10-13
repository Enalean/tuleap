<?php

$client = new SoapClient('http://shunt.cro.enalean.com/soap/codendi.wsdl.php?wsdl');

// Disciplus is a restricted user
$session = $client->login('', '')->session_hash;

// Disciplus 2 is not member of a project disciplus_1 is member of
var_dump($client->getProjectGroupsAndUsers($session, 104));

$client->logout($session);

?>
