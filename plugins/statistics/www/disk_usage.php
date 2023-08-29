<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Statistics\DiskUsageProjectsPresenterBuilder;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/Statistics_DiskUsageHtml.class.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginEnabled($p)) {
    $GLOBALS['Response']->redirect('/');
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

$disk_usage_dao = new Statistics_DiskUsageDao();
$svn_log_dao    = new SVN_LogDao();
$svn_retriever  = new SVNRetriever($disk_usage_dao);
$svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);
$duMgr          = new Statistics_DiskUsageManager(
    $disk_usage_dao,
    $svn_collector,
    EventManager::instance()
);

$duHtml = new Statistics_DiskUsageHtml($duMgr);

$disk_usage_output                = new Statistics_DiskUsageOutput(
    $duMgr
);
$disk_usage_graph                 = new Statistics_DiskUsageGraph(
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
$disk_usage_projects_builder      = new DiskUsageProjectsPresenterBuilder(
    $duMgr,
    $disk_usage_output,
    $disk_usage_search_fields_builder,
    $disk_usage_services_builder
);

$disk_usage_global_builder = new DiskUsageGlobalPresenterBuilder(
    $duMgr,
    $disk_usage_output
);

$disk_usage_router = new DiskUsageRouter(
    $disk_usage_services_builder,
    $disk_usage_projects_builder,
    $disk_usage_global_builder,
);

$disk_usage_router->route($request);
