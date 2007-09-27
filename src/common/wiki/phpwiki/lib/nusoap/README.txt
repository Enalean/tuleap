NuSOAP - Web Services Toolkit for PHP

Copyright (c) 2002 NuSphere Corporation
Copyright (c) 2003 Dietrich Ayala

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

If you have any questions or comments, please email or visit the website:

The development of this was once sponsored by NuSphere
http://www.nusphere.com

Information and updates are available at:
http://dietrich.ganx4.com/nusoap

For support, you can join the NuSOAP mailing list here:
https://lists.sourceforge.net/lists/listinfo/nusoap-general

Version: 0.6.4

WHAT IS NuSOAP?

NuSOAP is a set of PHP classes that allow users to send and receive
SOAP messages. Also included are utility classes for parsing WSDL
files and XML Schemas.

INSTALLATION

Enter this line at the top of your script:

include('/path/to/nusoap.php');

USAGE EXAMPLES:

BASIC SERVER EXAMPLE

<?php

require_once('nusoap.php');
$s = new soap_server;
$s->register('hello');
function hello($name){
	// optionally catch an error and return a fault
	if($name == ''){
    	return new soap_fault('Client','','Must supply a valid name.');
    }
	return "hello $name!";
}
$s->service($HTTP_RAW_POST_DATA);

?>

BASIC CLIENT USAGE EXAMPLE

<?php

require_once('nusoap.php');
$parameters = array('name'=>'dietrich');
$soapclient = new soapclient('http://someSOAPServer.com/hello.php');
echo $soapclient->call('hello',$parameters);

?>

WSDL CLIENT USAGE EXAMPLE

<?php

require_once('nusoap.php');
$parameters = array('dietrich');
$soapclient = new soapclient('http://someSOAPServer.com/hello.wsdl','wsdl');
echo $soapclient->call('hello',$parameters);

?>

PROXY CLIENT USAGE EXAMPLE (only works w/ wsdl)

<?php

require_once('nusoap.php');
$soapclient = new soapclient('http://someSOAPServer.com/hello.wsdl','wsdl');
$soap_proxy = $soapclient->getProxy();
echo $soap_proxy->hello('dietrich');

?>
