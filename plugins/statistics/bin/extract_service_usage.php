#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';
require_once __DIR__ . '/../vendor/autoload.php';

if ($argc !== 4) {
    fwrite(STDERR, "Usage: {$argv[0]} start_date end_date output" . PHP_EOL);
    exit(1);
}

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Unsufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$start_date = $argv[1];
$end_date   = $argv[2];
$output     = $argv[3];

$rule_valid_date = new Rule_Date();

if (! $rule_valid_date->isValid($start_date)) {
    fwrite(STDERR, 'Start date is not valid. Expected format is yyyy-mm-dd.' . PHP_EOL);
    exit(1);
}

if (! $rule_valid_date->isValid($end_date)) {
    fwrite(STDERR, 'End date is not valid. Expected format is yyyy-mm-dd.' . PHP_EOL);
    exit(1);
}

if ($start_date > $end_date) {
    fwrite(STDERR, 'Start date must be lesser or equal than end date.' . PHP_EOL);
    exit(1);
}

$content  = '';
$content .= "Start date : $start_date \n";
$content .= "End date : $end_date \n\n";

$dao          = new Statistics_ServicesUsageDao(CodendiDataAccess::instance(), $start_date, $end_date);
$csv_exporter = new Statistics_Services_UsageFormatter(new Statistics_Formatter($start_date, $end_date, get_csv_separator()));

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
    PluginManager::instance(),
    $event_manager
);

$content .= $csv_builder->buildServiceUsageCSVContent($start_date, $end_date);

file_put_contents($output, $content);
