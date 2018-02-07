<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\Permission\AdditionalPanesPermissionPerGroupBuilder;
use Tuleap\Project\Admin\Permission\PermissionPerGroupBuilder;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPresenter;

require_once('pre.php');

$request  = HTTPRequest::instance();
$group_id = $request->getValidated('group_id', 'GroupId', 0);

session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$title = _('Permissions per group');

$navigation_displayer = new HeaderNavigationDisplayer();
$navigation_displayer->displayBurningParrotNavigation($title, $project, 'permissions');

$ugroup_manager    = new UGroupManager();
$presenter_builder = new PermissionPerGroupBuilder($ugroup_manager);
$groups            = $presenter_builder->buildUGroup($project, $request);

$additional_panes_builder = new AdditionalPanesPermissionPerGroupBuilder(EventManager::instance());
$additional_panes         = $additional_panes_builder->buildAdditionalPresenters($project, $request->get('group'));

$presenter = new PermissionPerGroupPresenter($project, $groups, $additional_panes);


$templates_dir = ForgeConfig::get('tuleap_dir') . '/src/templates/project/admin/';
TemplateRendererFactory::build()
    ->getRenderer($templates_dir)
    ->renderToPage('permission-per-group', $presenter);

project_admin_footer(array());
