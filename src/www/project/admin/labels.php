<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Label\AllowedColorsCollection;
use Tuleap\Label\CollectionOfLabelableDao;
use Tuleap\Label\ColorPresenterFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Label\AddController;
use Tuleap\Project\Label\EditController;
use Tuleap\Project\Label\LabelsManagementURLBuilder;
use Tuleap\Project\Label\DeleteController;
use Tuleap\Project\Label\IndexController;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Label\LabelsManagementRouter;

require_once('pre.php');

$request = HTTPRequest::instance();
session_require(array('group' => $request->get('group_id'), 'admin_flags' => 'A'));

$url_builder   = new LabelsManagementURLBuilder();
$dao           = new LabelDao();
$history_dao   = new ProjectHistoryDao();
$event_manager = EventManager::instance();
$assets        = new IncludeAssets(ForgeConfig::get('codendi_dir').'/src/www/assets', '/assets');
$colors        = new AllowedColorsCollection();
$color_factory = new ColorPresenterFactory($colors);

$labelable_daos = new CollectionOfLabelableDao();
$event_manager->processEvent($labelable_daos);

$router = new LabelsManagementRouter(
    new IndexController($url_builder, $dao, $labelable_daos, $assets, $color_factory),
    new DeleteController($url_builder, $dao, $history_dao, $labelable_daos),
    new EditController($url_builder, $dao, $history_dao, $labelable_daos, $colors),
    new AddController($url_builder, $dao, $history_dao, $colors)
);
$router->process($request);
