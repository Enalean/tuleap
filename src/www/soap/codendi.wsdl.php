<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/nusoap.php';
require_once __DIR__ . '/../include/utils_soap.php';

define('PERMISSION_DENIED_FAULT', '3016');

define('NUSOAP', 1);

// Check if we the server is in secure mode or not.
$request = HTTPRequest::instance();
$protocol = 'http';
if ($request->isSecure() || ForgeConfig::get('sys_https_host')) {
    $protocol = 'https';
}

$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol . '://' . $default_domain;

// Instantiate server object
$server = new soap_server();

//configureWSDL($serviceName,$namespace = false,$endpoint = false,$style='rpc', $transport = 'http://schemas.xmlsoap.org/soap/http');
$server->configureWSDL('CodendiAPI', $uri, false, 'rpc', 'http://schemas.xmlsoap.org/soap/http', $uri);

//include the common TYPES API
require_once('./common/types.php');

//include the common SESSION API
require_once('./common/session.php');

// include the common GROUP API
require_once('./common/group.php');

// include the common USERS API
require_once('common/users.php');

// include the TRACKER API
require_once('./tracker/tracker.php');

// include the FRS API
require_once('./frs/frs.php');

// include the <Plugin> API (only if plugin is available), not tracker v5
$em = EventManager::instance();
$em->processEvent('soap', array());

// Call the service method to initiate the transaction and send the response
$server->service(file_get_contents('php://input'));
