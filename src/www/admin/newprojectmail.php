<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2016 - 2017. All rights reserved
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/proj_email.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$project_id = $request->getValidated('group_id', 'uint', 0);
$project    = ProjectManager::instance()->getProject($project_id);

if ($project && is_object($project) && !$project->isError()) {
    if (send_new_project_email($project)) {
        $msg = $GLOBALS['Language']->getText('admin_newprojectmail', 'success');
        $GLOBALS['Response']->addFeedback(Feedback::INFO, $msg);
    } else {
        $msg = $GLOBALS['Language']->getText('global', 'mail_failed', ForgeConfig::get('sys_email_admin'));
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $msg);
    }
}

$GLOBALS['Response']->redirect('/admin/groupedit.php?group_id=' . (int) $project_id);
