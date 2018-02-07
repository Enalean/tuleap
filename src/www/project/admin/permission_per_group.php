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
use Tuleap\Project\Admin\Permission\PermissionPerGroupPresenter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupBuilder;

require_once('pre.php');

$request  = HTTPRequest::instance();
$group_id = $request->getValidated('group_id', 'GroupId', 0);

session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$title = _('Permissions per group');

$navigation_displayer = new HeaderNavigationDisplayer();
$navigation_displayer->displayBurningParrotNavigation($title, $project, 'permissions');

$presenter_builder = new PermissionPerGroupUGroupBuilder(new UGroupManager());
$groups            = $presenter_builder->build($project);

$presenter = new PermissionPerGroupPresenter($project, $groups);

$templates_dir = ForgeConfig::get('tuleap_dir') . '/src/templates/project/admin/';
TemplateRendererFactory::build()
    ->getRenderer($templates_dir)
    ->renderToPage('permission-per-group', $presenter);

project_admin_footer(array());
