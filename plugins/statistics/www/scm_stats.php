<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/Statistics_ScmSvn.class.php';

$pluginManager = PluginManager::instance();
$p = $pluginManager->getPluginByName('statistics');
if (!$p || !$pluginManager->isPluginAvailable($p)) {
    header('Location: '.get_server_url());
}

// Grant access only to site admin
if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: '.get_server_url());
}

$request = HTTPRequest::instance();

    $vStartDate = new Valid('start');
    $vStartDate->addRule(new Rule_Date());
    $vStartDate->required();
    $startDate = $request->get('start');
    if ($request->valid($vStartDate)) {
        $startDate = $request->get('start');
    } else {
        $startDate = date('Y-m-d', strtotime('-1 year'));
    }

    $vEndDate = new Valid('end');
    $vEndDate->addRule(new Rule_Date());
    $vEndDate->required();
    $endDate = $request->get('end');
    if ($request->valid($vEndDate)) {
        $endDate = $request->get('end');
    } else {
        $endDate = date('Y-m-d');
    }
    // TODO: end date must be bigger than start date
    // TODO: Optionally set a group Id
    $groupId = null;

if ($request->exist('export')) {
    header ('Content-Type: text/csv');
    header ('Content-Disposition: filename=scm_stats.csv');
    $statsSvn = new Statistics_ScmSvn($startDate, $endDate, $groupId);
    echo $statsSvn->getStats();
    exit;
} else {
    $title = $GLOBALS['Language']->getText('plugin_statistics', 'scm_title');
    $GLOBALS['HTML']->includeCalendarScripts();
    $GLOBALS['HTML']->header(array('title' => $title));
    echo '<h1>'.$title.'</h1>';

    echo '<form name="form_scm_stats" method="set">';
    echo '<table>';
    echo '<tr>';
    echo '<td>';
    echo '<b>Start date</b>';
    echo '</td><td>';
    echo '<b>End date</b>';
    echo '</td><td>';
    echo '<b>Project Id</b>';
    echo '</td>';
    echo '</tr><tr>';
    echo '<td>';
    // TODO: update timestamp on change
    list($timestamp,) = util_date_to_unixtime($startDate);
    echo html_field_date('start', $startDate, false, 10, 10, 'form_scm_stats', false).'<br /><em>'.html_time_ago($timestamp).'</em><br />';
    echo '</td><td>';
    list($timestamp,) = util_date_to_unixtime($endDate);
    echo html_field_date('end', $endDate, false, 10, 10, 'form_scm_stats', false).'<br /><em>'.html_time_ago($timestamp).'</em><br />';
    echo '</td><td>';
    echo '<input name="group_id" >';
    echo '</td><td></tr><tr><td>';
    echo '<input type="submit" name="export" value="'.$GLOBALS['Language']->getText('plugin_statistics', 'scm_export_button').'" >';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</form>';

    $GLOBALS['HTML']->footer(array());
}

?>