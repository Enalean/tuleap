<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

require_once 'pre.php';
require_once 'SoapProject_Server.class.php';

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}
$uri = $protocol.'://'.$GLOBALS['sys_default_domain'].'/soap/project';

if ($request->exist('wsdl')) {
    require_once 'nusoap.php';
    
    // Instantiate server object
    $server = new soap_server();

    //configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
    $server->configureWSDL('TuleapProjectAPI', $uri, false, 'rpc', 'http://schemas.xmlsoap.org/soap/http', $uri);

    $server->register(
        'addProject',
        array(
            /*'sessionKey'     => 'xsd:string',*/
            'requesterLogin' => 'xsd:string',
            'shortName'      => 'xsd:string',
            'realName'       => 'xsd:string',
            'privacy'        => 'xsd:string',
            'templateId'     => 'xsd:int'),
        array('addProject' => 'xsd:int'),
        $uri,
        $uri.'#addProject',
        'rpc',
        'encoded',
        'This method throw an exception if there is a conflict on names or
         it there is an error during the creation process.
         It assumes a couple of things:
         * The project type is "Project" (Not modifiable)
         * The template is the default one (project id 100).
         * There is no "Project description" nor any "Project description
         * fields" (long desc, patents, IP, other software)
         * The project services are inherited from the template
         * There is no trove cat selected
         * The default Software Policy is "Site exchange policy".

         Projects are automatically accepted'
    );

    // Call the service method to initiate the transaction and send the response
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
    $server->service($HTTP_RAW_POST_DATA);
} else {
    $server = new SoapServer($uri.'/?wsdl',
                         array('cache_wsdl' => WSDL_CACHE_NONE));
    $server->setClass('SoapProject_Server');
    $server->handle();
}

?>
