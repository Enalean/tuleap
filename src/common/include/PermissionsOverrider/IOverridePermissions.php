<?php
/*
* Copyright (c) Enalean 2015. All Rights Reserved.
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

/**
 * Is implemented by local glue to override Tuleap's decision about
 * the access of a user to a project regarding very particular network/infrastructure
 * configurations
 */
interface PermissionsOverrider_IOverridePermissions
{

    /**
     * Allow to grant usage of anonymous even if platform would forbid it
     */
    public function forceUsageOfAnonymous();

    /**
     * @param PFUser $user      the user trying to gain access
     * @param Project $project  the incriminated project
     * @return bool true if user can finally access project
     */
    public function decideToLetUserAccessProjectEvenIfTuleapWouldNot(PFUser $user, Project $project);

    /**
     * @param PFUser $user  the user trying to gain access
     * @return bool true if user can finally access the platform
     */
    public function decideToLetUserAccessPlatformEvenIfTuleapWouldNot(PFUser $user);
}
