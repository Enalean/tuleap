<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Project;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeboxCreatorChecker
     */
    private $timebox_creator_checker;

    protected function setUp(): void
    {
        $this->timebox_creator_checker = $this->createMock(TimeboxCreatorChecker::class);
    }

    public function testDisallowArtifactCreationWhenItIsAProgramIncrementTrackerAndOtherChecksDoNotPass(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(false);

        $checker = new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
        );
        self::assertFalse(
            $checker->canCreateAProgramIncrement(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerIsNotProgramIncrement(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject(\Project::buildForTest())->build();

        $checker = new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement(),
        );
        self::assertTrue(
            $checker->canCreateAProgramIncrement(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenOtherChecksPass(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject($project)->build();
        $this->timebox_creator_checker->method('canTimeboxBeCreated')->willReturn(true);

        $checker = new ProgramIncrementCreatorChecker(
            $this->timebox_creator_checker,
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
        );
        self::assertTrue(
            $checker->canCreateAProgramIncrement(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }
}
