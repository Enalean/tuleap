<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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

require_once 'pre.php';

$request         = HTTPRequest::instance();
$project_manager = ProjectManager::instance();

$user      = $request->getCurrentUser();
$widget_id = $request->get('widget_id');

$dao = new \Tuleap\Dashboard\Widget\DashboardWidgetDao();
$row = $dao->searchWidgetInDashboardById($widget_id)->getRow();

if (! $row) {
    $GLOBALS['Response']->send400JSONErrors(_('We cannot find any edition information for the requested widget.'));
}

if ($row['dashboard_type'] === 'project' && ! $user->isAdmin($row['project_id'])) {
    $GLOBALS['Response']->send400JSONErrors(_('You must be a project admin to edit this widget.'));
}

if ($row['dashboard_type'] === 'user' && (int) $user->getId() !== (int) $row['user_id']) {
    $GLOBALS['Response']->send400JSONErrors(_('You can only edit your own widgets.'));
}

if ($row['dashboard_type'] === 'project') {
    $request->set('group_id', $row['project_id']);
}

$widget = \Widget::getInstance($row['name']);
$widget->owner_type = $row['project_id'] ? 'g' : 'u';
$widget->owner_id   = $row['project_id'] ? $row['project_id'] : $row['user_id'];
$widget->loadContent($row['content_id']);
echo $widget->getPreferencesForBurningParrot($row['id']);
