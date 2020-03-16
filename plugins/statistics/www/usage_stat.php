<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';

function csv_output($string)
{
    echo $string;
}

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

$sep = get_csv_separator();
$eol = "\r\n";

$min_year = 99999;
$max_year = 0;
$min_month = 13;
$max_month = 0;

$total_array = array();

$sql = 'SELECT YEAR(FROM_UNIXTIME(add_date)) as year, month(FROM_UNIXTIME(add_date)) as month, count(*) as c from user where user_id > 101 group by year,month';
$res = db_query($sql);
while ($row = db_fetch_array($res)) {
    if ($row['year'] < $min_year) {
        $min_year = $row['year'];
    }
    if ($row['year'] > $max_year) {
        $max_year = $row['year'];
        $max_month = 0; // Reset max month
    }

    if ($row['year'] == $min_year) {
        if ($row['month'] < $min_month) {
            $min_month = $row['month'];
        }
    }
    if ($row['year'] == $max_year) {
        if ($row['month'] > $max_month) {
            $max_month = $row['month'];
        }
    }

    $total_array[$row['year']][$row['month']]['user'] = $row['c'];
}


$sql = 'SELECT YEAR(FROM_UNIXTIME(register_time)) as year, month(FROM_UNIXTIME(register_time)) as month, count(*) as c from groups where group_id > 101 group by year,month';
$res = db_query($sql);
while ($row = db_fetch_array($res)) {
    if ($row['year'] < $min_year) {
        $min_year = $row['year'];
    }
    if ($row['year'] > $max_year) {
        $max_year = $row['year'];
    }

    if ($row['year'] == $min_year) {
        if ($row['month'] < $min_month) {
            $min_month = $row['month'];
        }
    }
    if ($row['year'] == $max_year) {
        if ($row['month'] > $max_month) {
            $max_month = $row['month'];
        }
    }

    $total_array[$row['year']][$row['month']]['group'] = $row['c'];
}

header('Content-Type: text/csv');
header('Content-Disposition: filename=Tuleap_progress_data.csv');

csv_output("Tuleap progress data$eol");
csv_output("Date" . $sep . "Registered User" . $sep . "Registered Projects" . $eol);

for ($year = $min_year; $year <= $max_year; $year++) {
    $y_min_month = 1;
    if ($year == $min_year) {
        $y_min_month = $min_month;
    }
    $y_max_month = 12;
    if ($year == $max_year) {
        $y_max_month = $max_month;
    }
    for ($month = $y_min_month; $month <= $y_max_month; $month++) {
        $user = 0;
        if (isset($total_array[$year][$month]['user'])) {
            $user = $total_array[$year][$month]['user'];
        }
        $group = 0;
        if (isset($total_array[$year][$month]['group'])) {
            $group = $total_array[$year][$month]['group'];
        }
        csv_output('1/' . $month . '/' . $year . $sep . $user . $sep . $group . $eol);
    }
}
