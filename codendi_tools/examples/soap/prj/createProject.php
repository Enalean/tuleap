<?php

if ($argc != 4) {
    die("Usage: ".$argv[0]." requester shortname longname\n");
}

$client = new SoapClient(null, array('location'   => "http://localhost:3080/soap2/",
                                     'uri'        => 'http://localhost:3080/soap2/',
                                     'cache_wsdl' => WSDL_CACHE_NONE));

var_dump($client->addProject($argv[1], $argv[2], $argv[3]));

?>