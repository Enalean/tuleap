<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class RetrieveTrackerOfArtifactStub implements RetrieveTrackerOfArtifact
{
    /**
     * @param TrackerIdentifier[] $trackers
     */
    private function __construct(private array $trackers)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withIds(int $tracker_id, int ...$other_tracker_ids): self
    {
        $identifiers = array_map(
            static fn(int $id) => TrackerIdentifierStub::withId($id),
            [$tracker_id, ...$other_tracker_ids]
        );
        return new self($identifiers);
    }

    #[\Override]
    public function getTrackerOfArtifact(ArtifactIdentifier $artifact): TrackerIdentifier
    {
        if (count($this->trackers) > 0) {
            return array_shift($this->trackers);
        }
        throw new \LogicException('No tracker configured');
    }
}
