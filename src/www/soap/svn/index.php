<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\SOAP\SOAPRequestValidatorImplementation;

require_once __DIR__ . '/../../include/pre.php';

// Check if we the server is in secure mode or not.
$request  = HTTPRequest::instance();
$protocol = 'http';
if ($request->isSecure() || ForgeConfig::get('sys_https_host')) {
    $protocol = 'https';
}
$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol . '://' . $default_domain . '/soap/svn';

$serviceClass = 'SVN_SOAPServer';

if ($request->exist('wsdl')) {
    $wsdlGen = new SOAP_NusoapWSDL($serviceClass, 'TuleapSubversionAPI', $uri);
    $wsdlGen->dumpWSDL();
} else {
    $user_manager = UserManager::instance();
    $soap_request_validator = $soap_request_validator = new SOAPRequestValidatorImplementation(
        ProjectManager::instance(),
        $user_manager,
        new ProjectAccessChecker(
            new PermissionsOverrider_PermissionsOverriderManager(),
            new RestrictedUserCanAccessProjectVerifier(),
            EventManager::instance()
        )
    );
    $svn_repository_listing = new SVN_RepositoryListing(new SVN_PermissionsManager(), new SVN_Svnlook(), $user_manager);

    $server = new TuleapSOAPServer(
        $uri . '/?wsdl',
        array('cache_wsdl' => WSDL_CACHE_NONE)
    );
    $server->setClass($serviceClass, $soap_request_validator, $svn_repository_listing);
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
}
