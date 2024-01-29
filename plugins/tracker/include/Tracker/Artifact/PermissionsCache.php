<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Tracker_Permission_PermissionChecker;
use Tracker_UserWithReadAllPermission;
use Tuleap\User\TuleapFunctionsUser;

class PermissionsCache
{
    /**
     * @var bool[][]
     */
    private static $can_view_cache = [];

    public static function userCanView(
        Artifact $artifact,
        PFUser $user,
        Tracker_Permission_PermissionChecker $permission_checker,
    ) {
        if ($user instanceof Tracker_UserWithReadAllPermission) {
            return true;
        }

        if ($user instanceof TuleapFunctionsUser) {
            return true;
        }

        if (isset(self::$can_view_cache[$artifact->getId()][$user->getId()])) {
            return self::$can_view_cache[$artifact->getId()][$user->getId()];
        }

        $user_can_view = $permission_checker->userCanView($user, $artifact);

        self::$can_view_cache[$artifact->getId()][$user->getId()] = $user_can_view;

        return $user_can_view;
    }
}
