<?php

$host = $_SERVER['TULEAP_SERVER'];
$soap_client  = new SoapClient("http://$host/soap/codendi.wsdl.php?wsdl");
$session_hash = $soap_client->login($_SERVER['TULEAP_USER'], $_SERVER['TULEAP_PASSWORD'])->session_hash;
$svn_client   = new SoapClient("http://$host/soap/svn/?wsdl", array('cache_wsdl' => WSDL_CACHE_NONE));

$start_date = mktime(0, 0, 0, 3, 1, 2012);
$end_date   = mktime(0, 0, 0, 8, 1, 2014);

echo "=== Top used files ===\n";
var_dump($svn_client->getSvnStatsFiles($session_hash, $argv[1], $start_date, $end_date, 10));

echo "=== Commiters ===\n";
var_dump($svn_client->getSvnStatsUsers($session_hash, $argv[1], $start_date, $end_date));

$soap_client->logout($session_hash);