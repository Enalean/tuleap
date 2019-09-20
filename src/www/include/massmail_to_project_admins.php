<?php
/**
 * Copyright (c) Enalean, 2014 - 2017. All Rights Reserved.
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

require_once __DIR__ . '/pre.php';

$csrf = new CSRFSynchronizerToken('');
$csrf->check('/my/');

$request           = HTTPRequest::instance();
$pm                = ProjectManager::instance();
$massmail_sender   = new MassmailSender();

$user              = $request->getCurrentUser();
$group_id          = $request->get('group_id');
$subject           = $request->get('subject');
$body              = $request->get('body');

$project           = $pm->getProject($group_id);
$admins            = $project->getAdmins();
$project_name      = $project->getPublicName();
$project_unix_name = $project->getUnixName();

$body_info         = $GLOBALS['Language']->getText('contact_admins', 'body_info');

$body = $body_info . $body;

if ($massmail_sender->sendMassmail($project, $user, $subject, $body, $admins)) {
    $GLOBALS['Response']->addFeedback(
        'info',
        $GLOBALS['Language']->getText('contact_admins', 'mail_sent_admin', array($project_name))
    );
} else {
    $GLOBALS['Response']->addFeedback(
        'error',
        $GLOBALS['Language']->getText('contact_admins', 'mail_not_sent_admin', array($project_name))
    );
}

$event_manager = EventManager::instance();
$event_manager->processEvent(
    Event::AFTER_MASSMAIL_TO_PROJECT_ADMINS,
    array()
);

$GLOBALS['Response']->redirect("/projects/" . $project->getUnixName());
