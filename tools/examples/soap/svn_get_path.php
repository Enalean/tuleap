<?php

$common_client = new SoapClient('http://shunt.cro.enalean.com/soap/codendi.wsdl.php?wsdl');

// Disciplus is a restricted user
$session = $common_client->login('manuel', 'manuel')->session_hash;

$svn_client = new SoapClient('http://shunt.cro.enalean.com/soap/svn/?wsdl', array('cache_wsdl' => WSDL_CACHE_NONE));

var_dump($svn_client->getSvnPath($session, 101, '/tags'));

$common_client->logout($session);

?>
