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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class RetrieveTrackerStub implements RetrieveTracker
{
    private ?int $project_id;

    private function __construct(?int $project_id)
    {
        $this->project_id = $project_id;
    }

    public function getTrackerById(int $tracker_id): ?ProgramTracker
    {
        if ($this->project_id === null) {
            return null;
        }

        return new ProgramTracker(
            TrackerTestBuilder::aTracker()
                ->withId($tracker_id)
                ->withProject(ProjectTestBuilder::aProject()->withId($this->project_id)->build())
            ->build()
        );
    }

    public static function buildValidTrackerWithProjectId(int $project_id): self
    {
        return new self($project_id);
    }

    public static function buildNullTracker(): self
    {
        return new self(null);
    }
}
