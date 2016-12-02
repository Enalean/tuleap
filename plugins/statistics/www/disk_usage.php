<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2009
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

use Tuleap\Statistics\DiskUsageGlobalPresenterBuilder;
use Tuleap\Statistics\DiskUsageRouter;
use Tuleap\Statistics\SearchFieldsPresenterBuilder;
use Tuleap\Statistics\DiskUsageServicesPresenterBuilder;
use Tuleap\Statistics\DiskUsageTopUsersPresenterBuilder;
use Tuleap\Statistics\DiskUsageProjectsPresenterBuilder;
use Tuleap\Statistics\DiskUsageUserDetailsPresenterBuilder;

require 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_DiskUsageHtml.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$duMgr  = new Statistics_DiskUsageManager();
$duHtml = new Statistics_DiskUsageHtml($duMgr);

$disk_usage_output = new Statistics_DiskUsageOutput(
    $duMgr
);
$disk_usage_graph = new Statistics_DiskUsageGraph(
    $duMgr
);
$disk_usage_search_fields_builder = new SearchFieldsPresenterBuilder();
$disk_usage_services_builder      = new DiskUsageServicesPresenterBuilder(
    ProjectManager::instance(),
    $duMgr,
    $disk_usage_output,
    $disk_usage_graph,
    $disk_usage_search_fields_builder
);
$disk_usage_projects_builder = new DiskUsageProjectsPresenterBuilder(
    $duMgr,
    $disk_usage_output,
    $disk_usage_search_fields_builder,
    $disk_usage_services_builder
);

$disk_usage_global_builder = new DiskUsageGlobalPresenterBuilder(
    $duMgr,
    $disk_usage_output
);

$top_users_builder = new DiskUsageTopUsersPresenterBuilder(
    $duMgr,
    $disk_usage_output
);

$user_details_builder = new DiskUsageUserDetailsPresenterBuilder(
    $duMgr,
    $disk_usage_output,
    $disk_usage_search_fields_builder,
    UserManager::instance()
);

$disk_usage_router = new DiskUsageRouter(
    $duMgr,
    $disk_usage_services_builder,
    $disk_usage_projects_builder,
    $top_users_builder,
    $disk_usage_global_builder,
    $user_details_builder
);

$disk_usage_router->route($request);
