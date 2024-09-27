<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException;

/**
 * @see NewTrackerThatCanBePlanned
 * @psalm-immutable
 */
final readonly class NewTrackerThatCanBePlannedCollection
{
    /**
     * @param list<NewTrackerThatCanBePlanned> $trackers
     */
    private function __construct(private array $trackers)
    {
    }

    /**
     * @param int[] $tracker_ids_that_can_be_planned
     * @throws TrackersThatCanBePlannedCannotBeEmptyException
     * @throws ProgramTrackerException
     */
    public static function fromIds(
        CheckNewPlannableTracker $tracker_checker,
        array $tracker_ids_that_can_be_planned,
        ProgramForAdministrationIdentifier $program,
    ): self {
        if ($tracker_ids_that_can_be_planned === []) {
            throw new TrackersThatCanBePlannedCannotBeEmptyException();
        }
        return new self(
            array_values(
                array_map(
                    static fn($tracker_id) => NewTrackerThatCanBePlanned::fromId($tracker_checker, $tracker_id, $program),
                    $tracker_ids_that_can_be_planned
                )
            )
        );
    }

    /**
     * @param list<NewTrackerThatCanBePlanned> $trackers_that_can_be_planned
     */
    public static function fromTrackers(array $trackers_that_can_be_planned): self
    {
        return new self($trackers_that_can_be_planned);
    }

    /** @return list<int> */
    public function getTrackerIds(): array
    {
        return array_map(
            static fn(NewTrackerThatCanBePlanned $tracker) => $tracker->id,
            $this->trackers
        );
    }
}
