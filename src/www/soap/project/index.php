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


$request = HTTPRequest::instance();

$uri = \Tuleap\ServerHostname::HTTPSUrl() . '/soap/project';

$serviceClass = Project_SOAPServer::class;

if ($request->exist('wsdl')) {
    $wsdlGen = new SOAP_NusoapWSDL($serviceClass, 'TuleapProjectAPI', $uri);
    $wsdlGen->dumpWSDL();
} else {
    $userManager    = UserManager::instance();
    $projectManager = ProjectManager::instance();

    $ugroup_dao = new UGroupDao();

    $generic_user_dao     = new GenericUserDao();
    $generic_user_factory = new GenericUserFactory($userManager, $projectManager, $generic_user_dao);

    $custom_project_description_dao       = new Project_CustomDescription_CustomDescriptionDao();
    $custom_project_description_value_dao = new Project_CustomDescription_CustomDescriptionValueDao();

    $custom_project_description_factory = new Project_CustomDescription_CustomDescriptionFactory($custom_project_description_dao);
    $custom_project_description_manager = new Project_CustomDescription_CustomDescriptionValueManager($custom_project_description_value_dao);

    $custom_project_description_value_factory = new Project_CustomDescription_CustomDescriptionValueFactory($custom_project_description_value_dao);

    $service_usage_dao     = new Project_Service_ServiceUsageDao();
    $service_usage_factory = new Project_Service_ServiceUsageFactory($service_usage_dao);
    $service_usage_manager = new Project_Service_ServiceUsageManager($service_usage_dao);

    $server = new TuleapSOAPServer(
        $uri . '/?wsdl',
        ['cache_wsdl' => WSDL_CACHE_NONE]
    );
    $server->setClass(
        $serviceClass,
        $projectManager,
        $userManager,
        $generic_user_factory,
        $custom_project_description_factory,
        $custom_project_description_manager,
        $custom_project_description_value_factory,
        $service_usage_factory,
        $service_usage_manager,
    );
    XML_Security::enableExternalLoadOfEntities(
        function () use ($server) {
            $server->handle();
        }
    );
}
