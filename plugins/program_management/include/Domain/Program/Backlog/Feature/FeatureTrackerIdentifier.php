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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

/**
 * I hold the identifier of a Feature Tracker.
 * Feature Trackers are chosen in Program Administration and can be planned in Program Increments.
 * @psalm-immutable
 */
final class FeatureTrackerIdentifier implements TrackerIdentifier
{
    private function __construct(private int $id)
    {
    }

    public static function fromFeature(
        RetrieveTrackerOfFeature $tracker_retriever,
        FeatureIdentifier $feature_identifier,
    ): self {
        return new self($tracker_retriever->getFeatureTracker($feature_identifier)->getId());
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
