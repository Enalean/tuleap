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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\IterationView;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserPreferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramBaseInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramFlagsStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramIncrementInfoStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramPrivacyStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserIsProgramAdminStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DisplayPlanIterationsPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 101;
    private const ITERATION_LABEL      = 'Cycles';
    private const ITERATION_SUB_LABEL  = 'cycle';

    public function testItBuilds(): void
    {
        $presenter = DisplayPlanIterationsPresenter::fromPlannedIterations(
            PlannedIterations::build(
                BuildProgramFlagsStub::withDefaults(),
                BuildProgramPrivacyStub::withPrivateAccess(),
                BuildProgramBaseInfoStub::withDefault(),
                BuildProgramIncrementInfoStub::withId(1260),
                VerifyUserIsProgramAdminStub::withProgramAdminUser(),
                ProgramIdentifierBuilder::build(),
                UserIdentifierStub::withId(666),
                ProgramIncrementIdentifierBuilder::buildWithId(1260),
                IterationTrackerConfigurationBuilder::buildWithIdAndLabels(
                    self::ITERATION_TRACKER_ID,
                    self::ITERATION_LABEL,
                    self::ITERATION_SUB_LABEL
                ),
                UserPreferenceBuilder::withPreference('accessibility_mode', '1')
            )
        );

        self::assertJsonStringEqualsJsonString('[{"label":"Top Secret","description":"For authorized eyes only"}]', $presenter->program_flags);
        self::assertJsonStringEqualsJsonString(
            '{"are_restricted_users_allowed":false,"project_is_public_incl_restricted":false,"project_is_private":true,"project_is_public":false,"project_is_private_incl_restricted":false,"explanation_text":"It is private, please go away","privacy_title":"Private","project_name":"Guinea Pig"}',
            $presenter->program_privacy
        );

        self::assertJsonStringEqualsJsonString('{"program_label":"Guinea Pig","program_shortname":"guinea-pig","program_icon":"\ud83d\udc39"}', $presenter->program);
        self::assertJsonStringEqualsJsonString('{"id":1260,"title":"Program increment #1260","start_date":"Oct 01","end_date":"Oct 31"}', $presenter->program_increment);
        self::assertJsonStringEqualsJsonString('{"label":"Cycles","sub_label":"cycle"}', $presenter->iterations_labels);
        self::assertTrue($presenter->is_user_admin);
        self::assertSame(self::ITERATION_TRACKER_ID, $presenter->iteration_tracker_id);
        self::assertTrue($presenter->is_accessibility_mode_enabled);
    }
}
