<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Statistics\FrequenciesPresenter;
use Tuleap\Statistics\FrequenciesSearchFieldsPresenterBuilder;

require 'pre.php';

// First, check plugin availability
$pluginManager = PluginManager::instance();
$p             = $pluginManager->getPluginByName('statistics');
if (! $p || ! $pluginManager->isPluginAvailable($p)) {
    header('Location: ' . get_server_url());
}

// Grant access only to site admin
if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    header('Location: ' . get_server_url());
}

$datastr = 'session';
if (is_array($request->get('type_values'))) {
    foreach ($request->get('type_values') as $k => $v) {
        $datastr = $v;
    }
}

$type_values = array();
if ($request->exist('type_values')) {
    $type_values = $request->get('type_values');
    if (! is_array($type_values)) {
        $type_values = explode(',', $type_values);
    }
} else {
    $type_values = array('session');
}

$date_value = date('Y') . '-0-0';
if ($request->exist('date') && $request->get('date') !== "") {
    $date_value = $request->get('date');
}
list($year, $month, $day) = explode('-', $date_value);

$advsrch = '0';
if ($request->exist('advsrch')) {
    $advsearch = $request->get('advsrch');
}

if (isset($_REQUEST['start']) && isset($_REQUEST['end'])) {
    if (strtotime($_REQUEST['start']) >= strtotime($_REQUEST['end']) || $_REQUEST['start'] == '' || $_REQUEST['end'] == '') {
        $GLOBALS['Response']->addFeedback('error', 'You make a mistake in selecting period. Please try again!');
    }
}

$startdate = false;
$enddate = false;
$filter = false;

$search_fields_builder = new FrequenciesSearchFieldsPresenterBuilder();
$search_fields_presenter = $search_fields_builder->build(
    $type_values,
    $date_value
);

$title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

$frequencies_presenter = new FrequenciesPresenter(
    $title,
    $search_fields_presenter,
    $year,
    $month,
    $day,
    $datastr,
    $advsrch,
    $startdate,
    $enddate,
    $filter
);

$admin_page_renderer = new AdminPageRenderer();
$admin_page_renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
    FrequenciesPresenter::TEMPLATE,
    $frequencies_presenter
);
