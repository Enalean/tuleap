<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 *
 */

use Tuleap\User\Account\RedirectAfterLogin;

// adduser.php - All the forms and functions to manage unix users
// Add user to an existing project
function account_add_user_to_group($group_id, &$user_unix_name)
{
    $um   = UserManager::instance();
    $user = $um->findUser($user_unix_name);
    if ($user) {
        $project = ProjectManager::instance()->getProject($group_id);
        if (! $project || $project->isError()) {
            return false;
        }
        $project_member_adder = \Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications::build();
        $project_member_adder->addProjectMember($user, $project);
        return true;
    } else {
        //user doesn't exist
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'user_not_exist'));
        return false;
    }
}

function account_redirect_after_login(PFUser $user, string $return_to): void
{
    global $pv;

    $event_manager        = EventManager::instance();
    $redirect_after_login = $event_manager->dispatch(new RedirectAfterLogin($user, $return_to, isset($pv) && $pv == 2));
    $return_to            = $redirect_after_login->getReturnTo();

    if ($return_to) {
        $returnToToken = parse_url($return_to);
        if (preg_match('{/my(/|/index.php|)}i', $returnToToken['path'] ?? '')) {
            if (strpos($return_to, '/my/') === 0) {
                $url       = $return_to;
                $return_to = '';
            } else {
                $url = '/my/index.php';
            }
        } else {
            $url = '/my/redirect.php';
        }
    } else {
        if (isset($pv) && $pv == 2) {
            $url = '/my/index.php?pv=2';
        } else {
            $url = '/my/index.php';
        }
    }

    $url_redirect = new URLRedirect($event_manager);
    $GLOBALS['Response']->redirect($url_redirect->makeReturnToUrl($url, $return_to));
}
