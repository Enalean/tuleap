<?php

$host         = 'shunt.cro.enalean.com';
$soap_client  = new SoapClient("http://$host/soap/codendi.wsdl.php?wsdl");
$session_hash = $soap_client->login('manuel', 'manuel')->session_hash;
$svn_client   = new SoapClient("http://$host/soap/svn/?wsdl", array('cache_wsdl' => WSDL_CACHE_NONE));

$start_date = mktime(0, 0, 0, 3, 1, 2012);
$end_date   = mktime(0, 0, 0, 5, 1, 2012);

var_dump($svn_client->getSvnStatsUser($session_hash, 101, $start_date, $end_date));
//var_dump($svn_client->getSvnStatsUser($session_hash, 111, 1, 108));

$soap_client->logout($session_hash);

?>
