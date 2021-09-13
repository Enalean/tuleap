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
namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTrackerFromProgram;

final class RetrieveTrackerFromProgramStub implements RetrieveTrackerFromProgram
{
    /**
     * @var ProgramTracker[]
     */
    private array $trackers;

    /**
     * @param ProgramTracker[] $trackers
     */
    private function __construct(array $trackers)
    {
        $this->trackers = $trackers;
    }

    /**
     * @return ProgramTracker[]
     */
    public function retrieveAllTrackersFromProgramId(ProgramForAdministrationIdentifier $program): array
    {
        return $this->trackers;
    }

    public static function fromProgramReference(ProgramTracker ...$trackers): self
    {
        return new self($trackers);
    }
}
