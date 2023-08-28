<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../include/Statistics_ServicesUsageDao.class.php';
require_once __DIR__ . '/../include/Statistics_Services_UsageFormatter.class.php';
require_once __DIR__ . '/../include/Statistics_Formatter.class.php';
require_once __DIR__ . '/../include/Statistics_DiskUsageHtml.class.php';
require_once __DIR__ . '/../include/CSV/CSVBuilder.php';
require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';

use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginEnabled($p)) {
    $GLOBALS['Response']->redirect('/');
}

//Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['Response']->redirect('/');
}

set_time_limit(180);

$request = HTTPRequest::instance();

$vStartDate = new Valid('services_usage_start_date');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('services_usage_start_date');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('services_usage_start_date');
} else {
    $startDate = date('Y-m-d', strtotime('-1 month'));
}

$vEndDate = new Valid('services_usage_end_date');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('services_usage_end_date');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('services_usage_end_date');
} else {
    $endDate = date('Y-m-d');
}

if ($startDate > $endDate) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        dgettext('tuleap-statistics', 'You made a mistake in selecting period. Please try again!')
    );
    $GLOBALS['Response']->redirect('/plugins/statistics/data_export.php');
}

if ($request->exist('export') && $startDate && $endDate) {
    header('Content-Type: text/csv');
    header('Content-Disposition: filename=services_usage_' . $startDate . '_' . $endDate . '.csv');
    echo "Start date : $startDate \n";
    echo "End date : $endDate \n\n";

    $dao          = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $startDate, $endDate);
    $csv_exporter = new Statistics_Services_UsageFormatter(new Statistics_Formatter($startDate, $endDate, get_csv_separator()));

    $custom_description_factory = new Project_CustomDescription_CustomDescriptionFactory(
        new Project_CustomDescription_CustomDescriptionDao()
    );

    $custom_description_value_dao = new Project_CustomDescription_CustomDescriptionValueDao();

    $trove_cat_dao     = new TroveCatDao();
    $trove_cat_factory = new TroveCatFactory($trove_cat_dao);
    $disk_usage_dao    = new Statistics_DiskUsageDao();
    $svn_log_dao       = new SVN_LogDao();
    $svn_retriever     = new SVNRetriever($disk_usage_dao);
    $svn_collector     = new SVNCollector($svn_log_dao, $svn_retriever);
    $event_manager     = EventManager::instance();

    $disk_usage_manager = new Statistics_DiskUsageManager(
        $disk_usage_dao,
        $svn_collector,
        $event_manager
    );

    $csv_builder = new \Tuleap\Statistics\CSV\CSVBuilder(
        $dao,
        $csv_exporter,
        $custom_description_factory,
        $custom_description_value_dao,
        $trove_cat_dao,
        $trove_cat_factory,
        $disk_usage_manager,
        $pluginManager,
        $event_manager
    );

    echo $csv_builder->buildServiceUsageCSVContent($startDate, $endDate);
}
