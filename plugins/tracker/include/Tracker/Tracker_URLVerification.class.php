<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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

class Tracker_URLVerification extends URLVerification
{
    protected function getUrl()
    {
        return new Tracker_URL();
    }

    /**
     * Ensure given user can access given project
     *
     * @throws Project_AccessProjectNotFoundException
     * @throws Project_AccessDeletedException
     * @throws Project_AccessRestrictedException
     * @throws Project_AccessPrivateException
     * @throws \Tuleap\Project\ProjectAccessSuspendedException
     * @throws \Tuleap\Project\AccessNotActiveException
     */
    public function userCanAccessProject(PFUser $user, Project $project): true
    {
        $permissions_checker = new \Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker(
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao())
        );
        if ($permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $user)) {
            return true;
        }

        return parent::userCanAccessProject($user, $project);
    }
}
