<?php

$host         = $_SERVER['TULEAP_SERVER'];
$soap_client  = new SoapClient("$host/soap/codendi.wsdl.php?wsdl");
$session_hash = $soap_client->login($_SERVER['TULEAP_USER'], $_SERVER['TULEAP_PASSWORD'])->session_hash;
$svn_client   = new SoapClient("$host/soap/svn/?wsdl", array('cache_wsdl' => WSDL_CACHE_NONE));

var_dump($svn_client->getSvnLog($session_hash, $argv[1], 50, ''));
var_dump($svn_client->getSvnLog($session_hash, $argv[1], 'sebn'));

$soap_client->logout($session_hash);

?>
