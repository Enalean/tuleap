<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

$membership_delegation_dao = new \Tuleap\Project\Admin\MembershipDelegationDao();

$url  = '/project/admin/editgroupinfo.php?';
$user = HTTPRequest::instance()->getCurrentUser();
if (! $user->isAdmin($group_id) && $membership_delegation_dao->doesUserHasMembershipDelegation($user->getId(), $group_id)) {
    $url  = '/project/admin/members.php?';
}

$GLOBALS['Response']->redirect(
    $url .
    http_build_query(
        array(
            'group_id' => $request->getProject()->getid()
        )
    )
);
