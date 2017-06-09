<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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

use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Widget\WidgetFactory;

require_once('pre.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/widget/Widget.class.php');

$request        = HTTPRequest::instance();
$csrf_token     = new CSRFSynchronizerToken('widget_management');
$layout_manager = new WidgetLayoutManager();
$good           = false;
$redirect       = '/';
$owner          = $request->get('owner');
$widget_factory = new WidgetFactory(
    UserManager::instance(),
    new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
    EventManager::instance()
);

if ($owner) {
    $owner_id   = (int)substr($owner, 1);
    $owner_type = substr($owner, 0, 1);
    switch($owner_type) {
        case WidgetLayoutManager::OWNER_TYPE_USER:
            $owner_id = user_getid();
            $redirect = '/my/';
            $good = true;
            break;
        case WidgetLayoutManager::OWNER_TYPE_GROUP:
            $pm = ProjectManager::instance();
            if ($project = $pm->getProject($owner_id)) {
                $group_id = $owner_id;
                $_REQUEST['group_id'] = $_GET['group_id'] = $group_id;
                $request->params['group_id'] = $group_id; //bad!
                $redirect = '/projects/'. $project->getUnixName().'/';
                if (!user_ismember($group_id, 'A') && !user_is_super_user()) {
                    $GLOBALS['Response']->redirect($redirect);
                }
                $good = true;
            }
            break;
        default:
            break;
    }
    if ($good) {
        $name = null;
        if ($request->exist('name')) {
            $param = $request->get('name');
            $name = array_pop(array_keys($param));
            $instance_id = (int)$param[$name];
        }

        $redirect .= '?'. http_build_query(
            array(
                'dashboard_id' => $request->get('dashboard_id')
            )
        );
        switch($request->get('action')) {
            case 'widget':
                $csrf_token->check($redirect, $request);
                if ($name) {
                    $widget = $widget_factory->getInstanceByWidgetName($name);
                    if ($widget && $widget->isAvailable()) {
                        $action = array_pop(array_keys($param[$name]));
                        switch($action) {
                            case 'add':
                                $widget_creator = new WidgetCreator(new DashboardWidgetDao($widget_factory));
                                try {
                                    $widget_creator->create(
                                        $owner_id,
                                        $owner_type,
                                        $request->get('dashboard_id'),
                                        $widget,
                                        $request
                                    );
                                } catch (Exception $exception) {
                                    $GLOBALS['HTML']->addFeedback(
                                        Feedback::ERROR,
                                        _('An error occured while trying to add the widget to the dashboard')
                                    );
                                }
                                break;
                        }
                    }
                }
                break;
        }
    }
}
if (!$request->isAjax()) {
    $GLOBALS['Response']->redirect($redirect);
}
