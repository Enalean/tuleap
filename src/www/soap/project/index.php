<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;

// Check if we the server is in secure mode or not.
$request  = HTTPRequest::instance();
$protocol = 'http';
if ($request->isSecure() || ForgeConfig::get('sys_https_host')) {
    $protocol = 'https';
}
$default_domain = ForgeConfig::get('sys_default_domain');

$uri = $protocol.'://'.$default_domain.'/soap/project';

$serviceClass = 'Project_SOAPServer';

if ($request->exist('wsdl')) {
    require_once 'common/soap/SOAP_NusoapWSDL.class.php';
    $wsdlGen = new SOAP_NusoapWSDL($serviceClass, 'TuleapProjectAPI', $uri);
    $wsdlGen->dumpWSDL();
} else {
    $userManager      = UserManager::instance();
    $projectManager   = ProjectManager::instance();
    $soapLimitFactory = new SOAP_RequestLimitatorFactory();

    $ugroup_dao         = new UGroupDao();
    $send_notifications = true;
    $ugroup_user_dao    = new UGroupUserDao();
    $ugroup_manager     = new UGroupManager();
    $ugroup_duplicator  = new Tuleap\Project\UgroupDuplicator(
        $ugroup_dao,
        $ugroup_manager,
        new UGroupBinding($ugroup_user_dao, $ugroup_manager),
        $ugroup_user_dao,
        EventManager::instance()
    );

    $widget_factory = new WidgetFactory(
        UserManager::instance(),
        new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
        EventManager::instance()
    );

    $widget_dao        = new DashboardWidgetDao($widget_factory);
    $project_dao       = new ProjectDashboardDao($widget_dao);
    $project_retriever = new ProjectDashboardRetriever($project_dao);
    $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
    $duplicator        = new ProjectDashboardDuplicator(
        $project_dao,
        $project_retriever,
        $widget_dao,
        $widget_retriever,
        $widget_factory
    );

    $force_activation = false;

    $projectCreator = new ProjectCreator(
        $projectManager,
        ReferenceManager::instance(),
        $userManager,
        $ugroup_duplicator,
        $send_notifications,
        new Tuleap\FRS\FRSPermissionCreator(
            new Tuleap\FRS\FRSPermissionDao(),
            $ugroup_dao
        ),
        $duplicator,
        new ServiceCreator(),
        new LabelDao(),
        $force_activation
    );

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

    $server = new TuleapSOAPServer($uri.'/?wsdl',
                             array('cache_wsdl' => WSDL_CACHE_NONE));
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
        $forge_ugroup_permissions_manager
    );
    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();
    $server->handle();
    $xml_security->disableExternalLoadOfEntities();
}
