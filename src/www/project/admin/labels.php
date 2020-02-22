<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use Tuleap\Color\AllowedColorsCollection;
use Tuleap\Label\CanProjectUseLabels;
use Tuleap\Label\CollectionOfLabelableDao;
use Tuleap\Color\ColorPresenterFactory;
use Tuleap\Project\Label\AddController;
use Tuleap\Project\Label\DeleteController;
use Tuleap\Project\Label\EditController;
use Tuleap\Project\Label\IndexController;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Label\LabelsManagementRouter;
use Tuleap\Project\Label\LabelsManagementURLBuilder;

require_once __DIR__ . '/../../include/pre.php';

$event_manager = EventManager::instance();
$request       = HTTPRequest::instance();
$project       = $request->getProject();
session_require(array('group' => $project->getID(), 'admin_flags' => 'A'));

$event = new CanProjectUseLabels($project);
$event_manager->processEvent($event);
if (! $event->areLabelsUsable()) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, gettext('No items can use labels in this project.'));
    $GLOBALS['Response']->redirect('/project/admin/?group_id=' . urlencode($project->getID()));
}

$url_builder   = new LabelsManagementURLBuilder();
$dao           = new LabelDao();
$history_dao   = new ProjectHistoryDao();
$colors        = new AllowedColorsCollection();
$color_factory = new ColorPresenterFactory($colors);

$labelable_daos = new CollectionOfLabelableDao();
$event_manager->processEvent($labelable_daos);

$router = new LabelsManagementRouter(
    new IndexController($url_builder, $dao, $labelable_daos, $color_factory),
    new DeleteController($url_builder, $dao, $history_dao, $labelable_daos, $event_manager),
    new EditController($url_builder, $dao, $history_dao, $labelable_daos, $colors, $event_manager),
    new AddController($url_builder, $dao, $history_dao, $colors)
);
$router->process($request);
