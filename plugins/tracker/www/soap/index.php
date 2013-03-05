<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

// Front controller of SOAP actions
// This script handle execution of SOAP requests
// In wsdl.php script there is WSDL generation thanks to NuSOAP
//    and nice display thanks to wsdl view

require_once 'pre.php';
require_once dirname(__FILE__).'/../../include/constants.php';

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$server_uri  = $protocol .'://'. Config::get('sys_default_domain');
$uri         = $server_uri . TRACKER_BASE_URL .'/soap';

if ($request->exist('wsdl')) {
    // Use a static wsdl file
    //$wsdl = file_get_contents(TRACKER_BASE_DIR .'/tracker.wsdl');
    //header('Content-type: text/xml');
    //echo str_replace('https://tuleap.example.com', $server_uri, $wsdl);
    //die();

    // Use nusoap to generate the wsdl
    require_once 'nusoap.php';
    require_once 'utils_soap.php';

    $server = new soap_server();
    $server->configureWSDL('TuleapTrackerV5API',$uri,false,'rpc','http://schemas.xmlsoap.org/soap/http',$uri);

    require_once TRACKER_BASE_DIR.'/wsdl.php';

    // Call the service method to initiate the transaction and send the response
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
    $server->service($HTTP_RAW_POST_DATA);

} else {
    require_once TRACKER_BASE_DIR.'/Tracker/SOAPServer.class.php';

    $soap_options = array();
    if (Config::get('DEBUG_MODE')) {
        $soap_options['cache_wsdl'] = WSDL_CACHE_NONE;
    }

    $formelement_factory = Tracker_FormElementFactory::instance();
    $server = new SoapServer($uri.'/?wsdl', $soap_options);

    $server->setClass(
        'Tracker_SOAPServer',
        new SOAP_RequestValidator(ProjectManager::instance(), UserManager::instance()),
        TrackerFactory::instance(),
        PermissionsManager::instance(),
        new Tracker_ReportDao(),
        $formelement_factory,
        Tracker_ArtifactFactory::instance(),
        Tracker_ReportFactory::instance(),
        new Tracker_FileInfoFactory(new Tracker_FileInfoDao, $formelement_factory)
    );
    $server->handle();
}

?>
