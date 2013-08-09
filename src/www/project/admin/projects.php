<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * http://sourceforge.net
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

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');

$group_id = $request->get('group_id');
if (! $group_id) {
    exit_missing_param();
} else {
    $project = ProjectManager::instance()->getProject($group_id);
    if ($project->isError() || $project->isDeleted()) {
        exit_error($Language->getText('project_admin_index','invalid_p'), $Language->getText('project_admin_index','p_not_found'));
    }

    session_require(array('group' => $group_id, 'admin_flags' => 'A'));
    if (! $request->isAjax()) {
        project_admin_header(array(
            'title' => $Language->getText('project_admin_index','p_admin', $project->getPublicName()),
            'group' => $group_id,
            'help'  => 'project-admin.html'
        ));
        echo '<h2>'. $GLOBALS['Language']->getText('project_admin_index', 'show_projects') .'</h2>';
    }

    if (count($project->getProjectsCreatedFrom())) {
        echo '<ul>';
        $template =& TemplateSingleton::instance();
        $i = 0;
        foreach($project->getProjectsCreatedFrom() as $subproject) {
            echo '<li>';
            if ($template->isTemplate($subproject['type'])) {
                echo '<b>';
            }
            echo '<a href="/projects/'. $subproject['unix_group_name'] .'">'. $subproject['group_name'] .'</a>';
            if ($template->isTemplate($subproject['type'])) {
                echo '</b>';
            }
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<em>'. $GLOBALS['Language']->getText('global', 'none') .'</em>';
    }

    if (! $request->isAjax()) {
        project_admin_footer(array());
    }
}
?>
