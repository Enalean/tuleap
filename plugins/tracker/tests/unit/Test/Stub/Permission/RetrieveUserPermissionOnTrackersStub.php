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
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Permission\UserPermissionsOnItems;
use Tuleap\Tracker\Tracker;

final class RetrieveUserPermissionOnTrackersStub implements RetrieveUserPermissionOnTrackers
{
    /**
     * @var array<string, list<int>>
     */
    private array $has_permission_on = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    /**
     * @param list<int> $tracker_ids
     */
    public function withPermissionOn(array $tracker_ids, TrackerPermissionType $permission): self
    {
        $this->has_permission_on[$permission->name] = $tracker_ids;

        return $this;
    }

    public function retrieveUserPermissionOnTrackers(PFUser $user, array $trackers, TrackerPermissionType $permission): UserPermissionsOnItems
    {
        if (isset($this->has_permission_on[$permission->name])) {
            return new UserPermissionsOnItems(
                $user,
                $permission,
                array_filter($trackers, fn(Tracker $tracker) => in_array($tracker->getId(), $this->has_permission_on[$permission->name])),
                array_filter($trackers, fn(Tracker $tracker) => ! in_array($tracker->getId(), $this->has_permission_on[$permission->name])),
            );
        }

        return new UserPermissionsOnItems($user, $permission, [], $trackers);
    }
}
