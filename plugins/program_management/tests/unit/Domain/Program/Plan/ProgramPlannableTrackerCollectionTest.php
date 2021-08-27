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
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramPlannableTrackerCollectionTest extends TestCase
{
    private ProgramForAdministrationIdentifier $program;
    private int $project_id;

    protected function setUp(): void
    {
        $this->project_id = 101;
        $this->program    = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testItThrowsAnExceptionWhenTrackerListIsEmpty(): void
    {
        $this->expectException(PlannableTrackerCannotBeEmptyException::class);
        ProgramPlannableTrackerCollection::fromIds(RetrieveTrackerStub::buildValidTrackerWithProjectId($this->project_id), [], $this->program);
    }

    public function testItBuildPlannableTrackers(): void
    {
        $tracker_id = 1;

        $retriever = RetrieveTrackerStub::buildValidTrackerWithProjectId($this->project_id);

        $expected = [ProgramPlannableTracker::build($retriever, $tracker_id, $this->program)];
        self::assertEquals($expected, ProgramPlannableTrackerCollection::fromIds($retriever, [$tracker_id], $this->program)->trackers);
    }
}
