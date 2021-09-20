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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTrackerFromProgram;

/**
 * I contain a list of TrackerReference. All TrackerReferences come from the same project.
 * @psalm-immutable
 * @see TrackerReference
 */
final class PotentialTrackerCollection
{
    /**
     * @var ProgramTracker[]
     */
    public array $trackers_reference;

    /**
     * @param ProgramTracker[] $trackers_reference
     */
    private function __construct(array $trackers_reference)
    {
        $this->trackers_reference = $trackers_reference;
    }

    public static function fromProgram(
        RetrieveTrackerFromProgram $trackers_retriever,
        ProgramForAdministrationIdentifier $program
    ): self {
        $trackers_reference = $trackers_retriever->retrieveAllTrackersFromProgramId($program);
        return new self($trackers_reference);
    }
}
