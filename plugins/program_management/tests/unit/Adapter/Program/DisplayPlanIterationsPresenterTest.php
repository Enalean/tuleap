<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;
use Tuleap\ProgramManagement\Tests\Builder\IterationsLabelsBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserIsProgramAdminStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class DisplayPlanIterationsPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuilds(): void
    {
        $presenter = DisplayPlanIterationsPresenter::fromPlannedIterations(
            PlannedIterations::build(
                BuildProgramFlagsStub::withDefaults(),
                BuildProgramPrivacyStub::withPrivateAccess(),
                BuildProgramBaseInfoStub::withDefault(),
                BuildProgramIncrementInfoStub::withId(1260),
                VerifyUserIsProgramAdminStub::withProgramAdminUser(),
                RetrieveVisibleIterationTrackerStub::withValidTracker(TrackerReferenceStub::withId(101)),
                ProgramIdentifierBuilder::build(),
                UserIdentifierStub::withId(666),
                ProgramIncrementIdentifierBuilder::buildWithId(1260),
                IterationsLabelsBuilder::buildWithLabels('Cycles', 'cycle')
            )
        );

        self::assertEquals('[{"label":"Top Secret","description":"For authorized eyes only"}]', $presenter->program_flags);
        self::assertEquals(
            '{"are_restricted_users_allowed":false,"project_is_public_incl_restricted":false,"project_is_private":true,"project_is_public":false,"project_is_private_incl_restricted":false,"explanation_text":"It is private, please go away","privacy_title":"Private","project_name":"Guinea Pig"}',
            $presenter->program_privacy
        );

        self::assertEquals('{"program_label":"Guinea Pig","program_shortname":"guinea-pig","program_icon":"\ud83d\udc39"}', $presenter->program);
        self::assertEquals('{"id":1260,"title":"Program increment #1260","start_date":"Oct 01","end_date":"Oct 31"}', $presenter->program_increment);
        self::assertEquals('{"label":"Cycles","sub_label":"cycle"}', $presenter->iterations_labels);
        self::assertTrue($presenter->is_user_admin);
        self::assertEquals('101', $presenter->iteration_tracker_id);
    }
}
