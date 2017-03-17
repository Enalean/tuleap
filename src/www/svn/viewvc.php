<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

use Tuleap\SvnCore\ViewVC\ViewVCProxy;

require_once('pre.php');

if (user_isloggedin()) {
    $vRoot = new Valid_String('root');
    $vRoot->required();
    if(! $request->valid($vRoot)) {
        exit_no_group();
    }
    $root = $request->get('root');
    $project_manager = ProjectManager::instance();
    $project = $project_manager->getProjectByUnixName($root);
    if (! $project) {
        exit_no_group();
    }
    $group_id = $project->getID();

    $viewvc_proxy = new ViewVCProxy();
    $viewvc_proxy->displayContent($project, $request);
} else {
    exit_not_logged_in();
}
