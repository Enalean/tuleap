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

if ($request->exist('export')) {
    $vStartDate = new Valid('start');
    $vStartDate->addRule(new Rule_Date());
    $vStartDate->required();
    $startDate = $request->get('start');
    if ($request->valid($vStartDate)) {
        $startDate = $request->get('start');
    } elseif (!empty($startDate)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils', 'verify_start_date'));
        $startDate = null;
    }

    $vEndDate = new Valid('end');
    $vEndDate->addRule(new Rule_Date());
    $vEndDate->required();
    $endDate = $request->get('end');
    if ($request->valid($vEndDate)) {
        $endDate = $request->get('end');
    } elseif (!empty($endDate)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils', 'verify_end_date'));
        $endDate = null;
    }

    // TODO: Optionally set a group Id
    $groupId = null;

    header ('Content-Type: text/csv');
    header ('Content-Disposition: filename=project_history.csv');
    $statsSvn = new Statistics_ScmSvn($startDate, $endDate, $groupId);
    echo $statsSvn->getHeader();
    echo $statsSvn->getStats();
    exit;
} else {
    // TODO: i18n
    $title = 'SCM stats';
    $GLOBALS['HTML']->includeCalendarScripts();
    $GLOBALS['HTML']->header(array('title' => $title));
    echo '<h1>'.$title.'</h1>';

    echo '<form>';
    echo html_field_date('start', '', false, 10, 10, '', false);
    echo html_field_date('end', '', false, 10, 10, '', false);
    echo '<input type="submit" name="export">';
    echo '</form>';

    $GLOBALS['HTML']->footer(array());
}

?>