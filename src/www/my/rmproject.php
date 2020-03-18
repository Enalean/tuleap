<?php
/**
 * Copyright 1999-2000 (c) The SourceForge Crew
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';
require_once __DIR__ . '/../project/admin/ugroup_utils.php';

if (user_isloggedin()) {
    $user_id = UserManager::instance()->getCurrentUser()->getId();

    $vGroupId = new Valid_GroupId();
    $vGroupId->required();
    if ($request->valid($vGroupId)) {
        $group_id = $request->get('group_id');
    } else {
        exit_no_group();
        exit();
    }

    $user_remover = new \Tuleap\Project\UserRemover(
        ProjectManager::instance(),
        EventManager::instance(),
        new ArtifactTypeFactory(false),
        new \Tuleap\Project\UserRemoverDao(),
        UserManager::instance(),
        new ProjectHistoryDao(),
        new UGroupManager()
    );
    //Process MEMBERSHIP_DELETE event
    $user_remover->removeUserFromProject($group_id, $user_id, false);

    /********* mail the changes so the admins know what happened *********/
    $res_admin = db_query("SELECT user.user_id AS user_id, user.email AS email, user.user_name AS user_name FROM user,user_group
		WHERE user_group.user_id=user.user_id AND user_group.group_id=" . db_ei($group_id) . " AND
		user_group.admin_flags = 'A'
		UNION
		SELECT user.user_id AS user_id, user.email AS email, user.user_name AS user_name
		FROM user
		    INNER JOIN ugroup_user ON (user.user_id = ugroup_user.user_id)
		    INNER JOIN ugroup ON (ugroup_user.ugroup_id = ugroup.ugroup_id AND ugroup.group_id = " . db_ei($group_id) . ")
		    INNER JOIN project_membership_delegation AS delegation ON (ugroup_user.ugroup_id = delegation.ugroup_id)
    ");
    $to = '';
    while ($row_admin = db_fetch_array($res_admin)) {
        $to .= "$row_admin[email],";
    }
    if (strlen($to) > 0) {
        $to = substr($to, 0, -1);

        $project = new Project($group_id);
        $project_name = $project->getPublicName();

        list($host,$port) = explode(':', $GLOBALS['sys_default_domain']);
        $link_members = HTTPRequest::instance()->getServerUrl() . "/project/memberlist.php?group_id=$group_id";
        $subject = $Language->getText('bookmark_rmproject', 'mail_subject', array($GLOBALS['sys_name'],user_getname($user_id),$project_name));
        $body = stripcslashes($Language->getText('bookmark_rmproject', 'mail_body', array($project_name, user_getname($user_id),$link_members)));
        $mail = new Codendi_Mail();
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setFrom($GLOBALS['sys_noreply']);
        $mail->setBodyText($body);
        $mail->send();
    }
    // display the personal page again
    session_redirect("/my/");
} else {
    exit_not_logged_in();
}
