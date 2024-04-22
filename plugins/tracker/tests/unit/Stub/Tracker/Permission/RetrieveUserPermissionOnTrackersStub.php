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

namespace Tuleap\Tracker\Test\Stub\Tracker\Permission;

use PFUser;
use Tracker;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\Permission\UserPermissionsOnItems;

final readonly class RetrieveUserPermissionOnTrackersStub implements RetrieveUserPermissionOnTrackers
{
    /**
     * @param list<int> $has_permission
     */
    private function __construct(private array $has_permission)
    {
    }

    /**
     * @param list<int> $tracker_ids
     */
    public static function buildWithPermissionOn(array $tracker_ids): self
    {
        return new self($tracker_ids);
    }

    public static function buildWithNoPermissions(): self
    {
        return new self([]);
    }

    public function retrieveUserPermissionOnTrackers(PFUser $user, array $trackers): UserPermissionsOnItems
    {
        return new UserPermissionsOnItems(
            $user,
            TrackerPermissionType::PERMISSION_VIEW,
            array_filter($trackers, fn(Tracker $tracker) => in_array($tracker->getId(), $this->has_permission)),
            array_filter($trackers, fn(Tracker $tracker) => ! in_array($tracker->getId(), $this->has_permission)),
        );
    }
}
