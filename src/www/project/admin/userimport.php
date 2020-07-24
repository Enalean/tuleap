<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../../include/account.php';

if (! user_isloggedin()) {
    exit_not_logged_in();
    return;
}


$project_id = $request->get('project_id');
if (! $project_id) {
    exit_no_group();
}

$membership_delegation_dao = new \Tuleap\Project\Admin\MembershipDelegationDao();
$user                      = $request->getCurrentUser();
if (! $user->isAdmin($project_id) && ! $membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $project_id)) {
    exit_error(
        $Language->getText('include_session', 'insufficient_g_access'),
        $Language->getText('include_session', 'no_perm_to_view')
    );
}

$user_manager  = UserManager::instance();
$import        = new UserImport(
    $user_manager,
    new UserHelper(),
    ProjectMemberAdderWithStatusCheckAndNotifications::build()
);
$user_filename = $_FILES['user_filename']['tmp_name'];

if (! file_exists($user_filename) || ! is_readable($user_filename)) {
    return $GLOBALS['Response']->send400JSONErrors(['error' => _('You should provide a file in entry.')]);
}

$user_collection = $import->parse($request->get('project_id'), $user_filename);

$GLOBALS['Response']->sendJSON(
    [
        'users'                  => $user_collection->getFormattedUsers(),
        'warning_multiple_users' => $user_collection->getWarningsMultipleUsers(),
        'warning_inavlid_users'  => $user_collection->getWarningsInvalidUsers()
    ]
);
