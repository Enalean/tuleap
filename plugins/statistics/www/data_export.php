<?php
/**
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

use Tuleap\Statistics\DataExportPresenterBuilder;
use Tuleap\Statistics\DataExportRouter;

require_once __DIR__ . '/../../../src/www/include/pre.php';

$plugin_manager = PluginManager::instance();
$plugin         = $plugin_manager->getPluginByName('statistics');
if (! $plugin || ! $plugin_manager->isPluginAvailable($plugin)) {
    $GLOBALS['HTML']->redirect('/');
}

if (! UserManager::instance()->getCurrentUser()->isSuperUser()) {
    $GLOBALS['HTML']->redirect('/');
}

$request = HTTPRequest::instance();

$data_export_router = new DataExportRouter(
    new DataExportPresenterBuilder()
);

$data_export_router->route($request);
