<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\RetrieveProjectFromTracker;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\TrackerReference;

final class RetrieveProjectFromTrackerStub implements RetrieveProjectFromTracker
{
    private function __construct(private ProjectReference $project_reference)
    {
    }

    public static function buildGeneric(): self
    {
        return new self(ProjectReferenceStub::buildGeneric());
    }

    #[\Override]
    public function fromTrackerReference(TrackerReference $tracker): ProjectReference
    {
        return $this->project_reference;
    }
}
