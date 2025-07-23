<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub\Permission;

use PFUser;
use Tuleap\Tracker\Permission\ArtifactPermissionType;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnArtifacts;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnFields;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Permission\UserPermissionsOnItems;

final readonly class TrackersPermissionsPassthroughRetriever implements RetrieveUserPermissionOnFields, RetrieveUserPermissionOnTrackers, RetrieveUserPermissionOnArtifacts
{
    #[\Override]
    public function retrieveUserPermissionOnArtifacts(
        PFUser $user,
        array $artifacts,
        ArtifactPermissionType $permission,
    ): UserPermissionsOnItems {
        return new UserPermissionsOnItems($user, $permission, $artifacts, []);
    }

    #[\Override]
    public function retrieveUserPermissionOnFields(
        PFUser $user,
        array $fields,
        FieldPermissionType $permission,
    ): UserPermissionsOnItems {
        return new UserPermissionsOnItems($user, $permission, $fields, []);
    }

    #[\Override]
    public function retrieveUserPermissionOnTrackers(
        PFUser $user,
        array $trackers,
        TrackerPermissionType $permission,
    ): UserPermissionsOnItems {
        return new UserPermissionsOnItems($user, $permission, $trackers, []);
    }
}
