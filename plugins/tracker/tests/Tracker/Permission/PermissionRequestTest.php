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

class Tracker_Permission_PermissionRequestTest extends TuleapTestCase
{
    protected $minimal_ugroup_list;

    public function setUp()
    {
        parent::setUp();
        $this->minimal_ugroup_list = array(
            ProjectUGroup::ANONYMOUS,
            ProjectUGroup::REGISTERED,
            ProjectUGroup::PROJECT_MEMBERS,
            ProjectUGroup::PROJECT_ADMIN
        );
    }

    public function itHasPermissionsBasedOnGroupIds()
    {
        $request = aRequest()
            ->with(
                Tracker_Permission_Command::PERMISSION_PREFIX.ProjectUGroup::ANONYMOUS,
                Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
            )
            ->with(
                Tracker_Permission_Command::PERMISSION_PREFIX.ProjectUGroup::REGISTERED,
                Tracker_Permission_Command::PERMISSION_FULL
            )
            ->build();
        $set_permission_request = new Tracker_Permission_PermissionRequest(array());
        $set_permission_request->setFromRequest($request, $this->minimal_ugroup_list);

        $this->assertEqual($set_permission_request->getPermissionType(ProjectUGroup::ANONYMOUS), Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY);
        $this->assertEqual($set_permission_request->getPermissionType(ProjectUGroup::REGISTERED), Tracker_Permission_Command::PERMISSION_FULL);
    }

    public function itRevokesPermissions()
    {
        $request = aRequest()
            ->with(
                Tracker_Permission_Command::PERMISSION_PREFIX.ProjectUGroup::ANONYMOUS,
                Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
            )
            ->with(
                Tracker_Permission_Command::PERMISSION_PREFIX.ProjectUGroup::REGISTERED,
                Tracker_Permission_Command::PERMISSION_FULL
            )
            ->build();
        $set_permission_request = new Tracker_Permission_PermissionRequest(array());
        $set_permission_request->setFromRequest($request, $this->minimal_ugroup_list);

        $set_permission_request->revoke(ProjectUGroup::REGISTERED);
        $this->assertNull($set_permission_request->getPermissionType(ProjectUGroup::REGISTERED));
    }
}
