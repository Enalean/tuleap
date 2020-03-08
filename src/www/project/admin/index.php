<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../include/pre.php';

$membership_delegation_dao = new \Tuleap\Project\Admin\MembershipDelegationDao();

$request  = HTTPRequest::instance();
$group_id = $request->getProject()->getID();

$url  = '/project/admin/editgroupinfo.php?' .
    http_build_query(
        array(
            'group_id' => $group_id
        )
    );

$user = $request->getCurrentUser();
if (! $user->isAdmin($group_id) && $membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $group_id)) {
    $url  = '/project/' . $request->getProject()->getid() . '/admin/members';
}

$GLOBALS['Response']->redirect(
    $url
);
