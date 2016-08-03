<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');
require_once('www/file/file_utils.php');

use Tuleap\FRS\PermissionController;

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_error($Language->getText('file_file_utils', 'g_id_err'), $Language->getText('file_file_utils', 'g_id_err'));
}
if (!user_ismember($group_id, 'R2')) {
    exit_permission_denied();
}

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$renderer        = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/frs');
$controller      = new PermissionController();

$controller->displayToolbar($project);
$controller->displayPermissions();
