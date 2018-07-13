<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Service\AddController;
use Tuleap\Project\Service\AdminRouter;
use Tuleap\Project\Service\DeleteController;
use Tuleap\Project\Service\EditController;
use Tuleap\Project\Service\IndexController;
use Tuleap\Project\Service\ServiceCreator;
use Tuleap\Project\Service\ServicePOSTDataBuilder;
use Tuleap\Project\Service\ServicesPresenterBuilder;
use Tuleap\Project\Service\ServiceUpdator;

require_once('pre.php');
require_once('www/project/admin/project_admin_utils.php');

$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'uint', 0);

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$service_manager = ServiceManager::instance();
$project_manager = ProjectManager::instance();
$service_dao     = new ServiceDao();

$event_manager = EventManager::instance();
$router        = new AdminRouter(
    new IndexController(
        new ServicesPresenterBuilder(ServiceManager::instance(), $event_manager),
        new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets'),
        new HeaderNavigationDisplayer()
    ),
    new DeleteController($service_dao),
    new AddController(
        new ServiceCreator($service_dao, $project_manager),
        new ServicePOSTDataBuilder($event_manager)
    ),
    new EditController(
        new ServiceUpdator($service_dao, $project_manager, $service_manager),
        new ServicePOSTDataBuilder($event_manager),
        $service_manager
    )
);
$router->process($request);
