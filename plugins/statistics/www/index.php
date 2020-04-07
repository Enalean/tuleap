<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
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
use Tuleap\Statistics\AdminHeaderPresenter;
use Tuleap\Statistics\Frequencies\FrequenciesPresenter;
use Tuleap\Statistics\SearchFieldsPresenterBuilder;

require_once __DIR__ . '/../../../src/www/include/pre.php';

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

if (
    isset($_REQUEST['start'])
    && isset($_REQUEST['end'])
    && strtotime($_REQUEST['start']) > strtotime($_REQUEST['end'])
) {
    $date_error_message = $GLOBALS['Language']->getText('plugin_statistics', 'period_error');
    $GLOBALS['Response']->addFeedback('error', $date_error_message);
}

$v_start_date = new Valid('start');
$v_start_date->addRule(new Rule_Date());
$v_start_date->required();
$start_date = $request->get('start');
if ($request->valid($v_start_date)) {
    $start_date = $request->get('start');
} else {
    $start_date = date('Y-m-d', strtotime('-1 year'));
}

$v_end_date = new Valid('end');
$v_end_date->addRule(new Rule_Date());
$v_end_date->required();
$end_date = $request->get('end');
if ($request->valid($v_end_date)) {
    $end_date = $request->get('end');
} else {
    $end_date = date('Y-m-d');
}

if ($request->exist('filter')) {
    $filter = $request->get('filter');
} else {
    $filter = 'month';
}

$search_fields_builder = new SearchFieldsPresenterBuilder();
$search_fields_presenter = $search_fields_builder->buildSearchFieldsForFrequencies(
    $type_values,
    $filter,
    $start_date,
    $end_date
);

$title = $GLOBALS['Language']->getText('plugin_statistics', 'index_page_title');

$header_presenter = new AdminHeaderPresenter(
    $title,
    'frequencies'
);

$frequencies_presenter = new FrequenciesPresenter(
    $header_presenter,
    $search_fields_presenter,
    $datastr,
    $start_date,
    $end_date,
    $filter
);

$admin_page_renderer = new AdminPageRenderer();
$admin_page_renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/plugins/statistics/templates',
    FrequenciesPresenter::TEMPLATE,
    $frequencies_presenter
);
