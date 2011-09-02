<?php
   $xslDoc = new DOMDocument();
   $xslDoc->load("wsdl-viewer.xsl");

   $xmlDoc = new DOMDocument();
   $src = dirname(dirname($_SERVER['SCRIPT_URI']))."/plugins/tracker/soap/index.php?wsdl";
   $xml = file_get_contents($src);
   $xmlDoc->loadXML($xml);

   $proc = new XSLTProcessor();
   $proc->importStylesheet($xslDoc);
   echo $proc->transformToXML($xmlDoc);

?>
