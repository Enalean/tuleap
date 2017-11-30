<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once('pre.php');
require_once('www/include/account.php');

if (! user_isloggedin()) {
    exit_not_logged_in();
    return;
}

if (! $request->get('project_id')) {
    exit_no_group();
}

session_require(array('group' => $request->get('project_id'), 'admin_flags' => 'A'));

$user_manager  = UserManager::instance();
$import        = new UserImport($request->get('project_id'), $user_manager, new UserHelper());
$user_filename = $_FILES['user_filename']['tmp_name'];

if (!file_exists($user_filename) || !is_readable($user_filename)) {
    return $GLOBALS['Response']->send400JSONErrors(array('error' => _('You should provide a file in entry.')));
}

$user_collection = $import->parse($user_filename);

$GLOBALS['Response']->sendJSON(
    array(
        'users'                  => $user_collection->getFormattedUsers(),
        'warning_multiple_users' => $user_collection->getWarningsMultipleUsers(),
        'warning_inavlid_users'  => $user_collection->getWarningsInvalidUsers()
    )
);
