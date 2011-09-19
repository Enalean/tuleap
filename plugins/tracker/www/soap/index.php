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

require_once('pre.php');

//define('TULEAP_WS_API_VERSION', '4.1');

define('LOG_SOAP_REQUESTS', false);

// Check if we the server is in secure mode or not.
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
    $protocol = "https";
} else {
    $protocol = "http";
}

$uri = $protocol.'://'.$sys_default_domain;

if ($request->exist('wsdl')) {
    $GLOBALS['Response']->redirect('/plugins/tracker/soap/wsdl?wsdl');
}

try {
    $server = new SoapServer($uri.'/plugins/tracker/soap/wsdl?wsdl',
                                   array('trace'        => 1, 
                                         'soap_version' => SOAP_1_1,
                                         'cache_wsdl'   => WSDL_CACHE_NONE,
                                   )
                  );
    require_once(dirname(__FILE__).'/../../include/soap.php');
} catch (Exception $e) {
    echo $e;
}


// if POST was used to send this request, we handle it
// else, we display a list of available methods
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (LOG_SOAP_REQUESTS) {
        error_log('SOAP Request :');
        error_log($HTTP_RAW_POST_DATA);
    }
    $server->handle();
} else {
    echo '<strong>This SOAP server can handle following functions : </strong>';
    echo '<ul>';
    foreach($server->getFunctions() as $func) {
        echo '<li>' , $func , '</li>';
    }
    echo '</ul>';
    echo '<p><a href="/plugins/tracker/soap/wsdl?wsdl">You can access the raw WSDL</a> or <a href="/plugins/tracker/soap/view-wsdl">a human readable version of it</a></p>';
}

?>
