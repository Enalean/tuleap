<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'pre.php';
require_once 'common/mail/Codendi_Mail.class.php';
require_once 'common/mail/MassmailSender.class.php';
require_once('common/include/CSRFSynchronizerToken.class.php');

$csrf = new CSRFSynchronizerToken('massmail_to_project_members.php');
$csrf->check('/my/');

$request    = HTTPRequest::instance();
$pm         = ProjectManager::instance();

$user       = $request->getCurrentUser();
$group_id   = $request->get('group_id');
$subject    = $request->get('subject');
$body       = $request->get('body');

$project    = $pm->getProject($group_id);
$members    = $project->getMembers();


$massmail_sender = new MassmailSender();
$massmail_sender->sendMassmail($project, $user, $subject, $body, $members);
$GLOBALS['Response']->redirect("/my");

?>