<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../www/include/nusoap.php';

/**
 * Generate a WSDL for all public methods of a given class name
 */
class SOAP_NusoapWSDL
{
    private $className;
    private $serviceName;
    private $uri;

    public function __construct($className, $serviceName, $uri)
    {
        $this->className   = $className;
        $this->serviceName = $serviceName;
        $this->uri         = $uri;
    }

    public function dumpWSDL()
    {
        // Instantiate server object
        $server = new soap_server();
        $server->configureWSDL($this->serviceName, $this->uri, false, 'rpc', 'http://schemas.xmlsoap.org/soap/http', $this->uri);

        $this->appendMethods($server);
        $this->appendTypes($server);

        // Call the service method to initiate the transaction and send the response
        $server->service(file_get_contents('php://input'));
    }

    private function appendMethods(soap_server $server)
    {
        $reflection = new ReflectionClass($this->className);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getName() != '__construct') {
                $this->appendOneMethod($server, $method);
            }
        }
    }

    private function appendOneMethod(soap_server $server, ReflectionMethod $method)
    {
        $wsdlGen    = new SOAP_WSDLMethodGenerator($method);
        $server->register(
            $method->getName(),
            $wsdlGen->getParameters(),
            $wsdlGen->getReturnType(),
            $this->uri,
            $this->uri . '#' . $method->getName(),
            'rpc',
            'encoded',
            $wsdlGen->getHTMLFormattedComment()
        );
    }

    private function appendTypes($server)
    {
        include __DIR__ . '/../../www/soap/common/types.php';
    }
}
