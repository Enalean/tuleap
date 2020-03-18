<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\ErrorRendering;
use Tuleap\Request\CurrentPage;
use Tuleap\Request\FrontRouter;
use Tuleap\Request\RequestInstrumentation;
use Tuleap\Request\RouteCollector;

define('FRONT_ROUTER', true);

require_once __DIR__ . '/include/pre.php';

$router = new FrontRouter(
    new RouteCollector($event_manager),
    new URLVerificationFactory($event_manager),
    BackendLogger::getDefaultLogger(),
    new ErrorRendering(),
    new ThemeManager(
        new BurningParrotCompatiblePageDetector(
            new CurrentPage(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            )
        )
    ),
    PluginManager::instance(),
    new RequestInstrumentation(Prometheus::instance())
);
$router->route($request);
