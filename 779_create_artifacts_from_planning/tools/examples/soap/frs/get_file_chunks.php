<?php

// Codendi remote API documentation:
// http://www.w3.org/2000/06/webdata/xslt?xslfile=http://tomi.vanek.sk/xml/wsdl-viewer.xsl&xmlfile=http://www.codendi.org/soap/?wsdl&transform=Submit

// Instanciate new SOAP Client
// See documentation: http://www.php.net/manual/en/soapclient.soapclient.php
$soap = new SoapClient('codendi.org', array('cache_wsdl' => WSDL_CACHE_NONE));

// Call 'login' to retreive an authentication token
//$hash = $soap->login('login', 'password')->session_hash;
//var_dump($hash);
$hash = "2c07476b0016bef2ebad1cc8635430c4";


try {

    //    var_dump($soap->addFile($hash, 101, 3, 15, 'coinpan.txt', base64_encode(file_get_contents('toto.txt')), 100, 100));

    $chunkSize = 1000000;
    $i         = 0;
    $fd = fopen('coincoin.txt', 'wb');
    do {
        $content = base64_decode($soap->getFileChunk($hash, 101, 3, 15, 38, $i * $chunkSize, $chunkSize));
        $written = fwrite($fd, $content);
        $cLength = strlen($content);
        if ($written != $cLength) {
            throw new Exception('Received '.$cLength.' of data but only '.$written.' written on Disk');
        }
        $i++;
    } while ($cLength >= $chunkSize);
    fclose($fd);
}
catch (SoapFault $fault) {
    trigger_error("SOAP Fault: (faultcode: {$fault->faultcode}, faultstring: {$fault->faultstring})", E_USER_ERROR);
}


// Cancel session
//$soap->logout($hash);

?>