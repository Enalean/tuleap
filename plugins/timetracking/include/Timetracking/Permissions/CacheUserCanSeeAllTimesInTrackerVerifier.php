<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Permissions;

use PFUser;
use Tuleap\Tracker\Tracker;

final class CacheUserCanSeeAllTimesInTrackerVerifier implements VerifyUserCanSeeAllTimesInTracker
{
    /**
     * @var array<int, array<int, bool>>
     */
    private array $cache = [];

    public function __construct(private readonly VerifyUserCanSeeAllTimesInTracker $verifier)
    {
    }

    #[\Override]
    public function userCanSeeAllTimesInTracker(PFUser $user, Tracker $tracker): bool
    {
        $tracker_id = $tracker->getId();
        if (! isset($this->cache[$tracker_id])) {
            $this->cache[$tracker_id] = [];
        }

        $user_id = (int) $user->getId();
        if (! isset($this->cache[$tracker_id][$user_id])) {
            $this->cache[$tracker_id][$user_id] = $this->verifier->userCanSeeAllTimesInTracker($user, $tracker);
        }

        return $this->cache[$tracker_id][$user_id];
    }
}
