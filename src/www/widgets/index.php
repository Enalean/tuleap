<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Widget\Add\AddWidgetController;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\PreferencesController;
use Tuleap\Dashboard\Widget\Router;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Widget\WidgetFactory;

require_once __DIR__ . '/../include/pre.php';
session_write_close();
require_once __DIR__ . '/../my/my_utils.php';

$request = HTTPRequest::instance();

$widget_factory = new WidgetFactory(
    UserManager::instance(),
    new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
    EventManager::instance()
);

$dao = new DashboardWidgetDao($widget_factory);
$router = new Router(
    new PreferencesController(
        $dao,
        $widget_factory
    ),
    new AddWidgetController(
        $dao,
        $widget_factory,
        new WidgetCreator(new DashboardWidgetDao($widget_factory)),
        new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao())
    ),
    $widget_factory
);

$router->route($request);
