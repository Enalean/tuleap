<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/permissions.php';

$request = HTTPRequest::instance();
if ($request->exist('group_id')) {
    $group_id = $request->get('group_id');
    session_require(array('group' => $group_id,'admin_flags' => 'A'));

    echo '<table><tr><td><div style="overflow:auto; height:250px; border:1px solid gray">';

    // First make a quick hash of this project's restricted users
    $current_group_restricted_users = array();
    $sql = "SELECT user.user_id from user, user_group WHERE user.status='R' AND user.user_id=user_group.user_id AND user_group.group_id=$group_id";
    $res = db_query($sql);
    while ($row = db_fetch_array($res)) {
        $current_group_restricted_users[$row['user_id']] = true;
    }
    $hp = Codendi_HTMLPurifier::instance();
    $sql = "SELECT user_id, user_name, realname, status FROM user WHERE status='A' OR status='R' ORDER BY user_name";
    $res = db_query($sql);
    $member_id = array();
    while ($row = db_fetch_array($res)) {
        // Don't display restricted users that don't belong to the project
        if ($row['status'] == 'R') {
            if (!isset($current_group_restricted_users[$row['user_id']]) || !$current_group_restricted_users[$row['user_id']]) {
                continue;
            }
        }
        echo '<div><b>' . $row['user_name'] . '</b> (' . $hp->purify($row['realname'], CODENDI_PURIFIER_CONVERT_HTML) . ")</div>\n";
    }

    echo '</div></td></tr></table>';
} else {
    $feedback = new Feedback();
    $feedback->log('error', $GLOBALS['Language']->getText('global', 'missing_parameters'));
    echo $feedback->fetch();
}
