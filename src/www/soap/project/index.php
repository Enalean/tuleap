<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../include/pre.php';

use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

// Check if we the server is in secure mode or not.
$request  = HTTPRequest::instance();
$protocol = 'http';
if ($request->isSecure() || ForgeConfig::get('sys_https_host')) {
    $protocol = 'https';
}
$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol . '://' . $default_domain . '/soap/project';

$serviceClass = Project_SOAPServer::class;

if ($request->exist('wsdl')) {
    $wsdlGen = new SOAP_NusoapWSDL($serviceClass, 'TuleapProjectAPI', $uri);
    $wsdlGen->dumpWSDL();
} else {
    $userManager      = UserManager::instance();
    $projectManager   = ProjectManager::instance();
    $soapLimitFactory = new SOAP_RequestLimitatorFactory();

    $ugroup_dao         = new UGroupDao();

    $projectCreator = ProjectCreator::buildSelfRegularValidation();

    $generic_user_dao     = new GenericUserDao();
    $generic_user_factory = new GenericUserFactory($userManager, $projectManager, $generic_user_dao);
    $limitator            = $soapLimitFactory->getLimitator();

    $custom_project_description_dao       = new Project_CustomDescription_CustomDescriptionDao();
    $custom_project_description_value_dao = new Project_CustomDescription_CustomDescriptionValueDao();

    $custom_project_description_factory = new Project_CustomDescription_CustomDescriptionFactory($custom_project_description_dao);
    $custom_project_description_manager = new Project_CustomDescription_CustomDescriptionValueManager($custom_project_description_value_dao);

    $custom_project_description_value_factory = new Project_CustomDescription_CustomDescriptionValueFactory($custom_project_description_value_dao);

    $service_usage_dao     = new Project_Service_ServiceUsageDao();
    $service_usage_factory = new Project_Service_ServiceUsageFactory($service_usage_dao);
    $service_usage_manager = new Project_Service_ServiceUsageManager($service_usage_dao);

    $forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
        new User_ForgeUserGroupPermissionsDao()
    );

    $server = new TuleapSOAPServer(
        $uri . '/?wsdl',
        array('cache_wsdl' => WSDL_CACHE_NONE)
    );
    $server->setClass(
        $serviceClass,
        $projectManager,
        $projectCreator,
        $userManager,
        $generic_user_factory,
        $limitator,
        $custom_project_description_factory,
        $custom_project_description_manager,
        $custom_project_description_value_factory,
        $service_usage_factory,
        $service_usage_manager,
        $forge_ugroup_permissions_manager,
        new ProjectRegistrationUserPermissionChecker(
            new ProjectDao()
        )
    );
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
}
