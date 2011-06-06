<?php

if ($argc < 4) {
    die("Usage: ".$argv[0]." requester shortname longname [member 1] [member 2] [...]\n");
}

$client = new SoapClient(null, array('location'   => "http://localhost:3080/soap2/",
                                     'uri'        => 'http://localhost:3080/soap2/',
                                     'cache_wsdl' => WSDL_CACHE_NONE));

/*$prjId = $client->addProject($argv[1], $argv[2], $argv[3]);

echo "New Project ID: $prjId\n";
*/

$prjId = 113;

for($i = 4; $i < $argc; $i++) {
    var_dump($client->addProjectMember($prjId, $argv[$i]));
}

for($i = 4; $i < $argc; $i++) {
    var_dump($client->removeProjectMember($prjId, $argv[$i]));
}

for($i = 4; $i < $argc; $i++) {
    var_dump($client->addProjectMember($prjId, $argv[$i]));
}

?>