<?php

$host         = 'tuleap.local';
$soap_client  = new SoapClient("http://$host/soap/codendi.wsdl.php?wsdl");
$session_hash = $soap_client->login('seb', 'secret')->session_hash;
$svn_client   = new SoapClient("http://$host/soap/svn/?wsdl", array('cache_wsdl' => WSDL_CACHE_NONE));

var_dump($svn_client->getSvnLog($session_hash, 111));
var_dump($svn_client->getSvnLog($session_hash, 111, 1, 108));

$soap_client->logout($session_hash);

?>
