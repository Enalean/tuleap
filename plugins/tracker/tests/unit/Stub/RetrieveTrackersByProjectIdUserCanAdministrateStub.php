<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use PFUser;
use Tracker;
use Tuleap\Tracker\RetrieveTrackersByProjectIdUserCanAdministrate;

final class RetrieveTrackersByProjectIdUserCanAdministrateStub implements RetrieveTrackersByProjectIdUserCanAdministrate
{
    /**
     * @param Tracker[] $trackers
     */
    private function __construct(private readonly array $trackers)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withTrackers(Tracker $first_tracker, Tracker ...$other_trackers): self
    {
        return new self([$first_tracker, ...$other_trackers]);
    }

    public function getTrackersByProjectIdUserCanAdministrate(int|string $project_id, PFUser $user): array
    {
        return $this->trackers;
    }
}
