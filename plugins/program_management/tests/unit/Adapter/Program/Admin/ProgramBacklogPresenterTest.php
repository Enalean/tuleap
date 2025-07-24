<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramBacklogConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;
use Tuleap\Project\Flags\ProjectFlagPresenter;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramBacklogPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID                   = 114;
    private const PROGRAM_LABEL                = 'Ichthulin';
    private const PROGRAM_SHORT_NAME           = 'unstern';
    private const PROGRAM_INCREMENT_TRACKER_ID = 54;
    private const PROGRAM_INCREMENT_LABEL      = 'Program Increments';
    private const PROGRAM_INCREMENT_SUB_LABEL  = 'program increment';
    private const ITERATION_LABEL              = 'Iterations';
    private ProgramBacklogConfiguration $configuration;

    #[\Override]
    protected function setUp(): void
    {
        $program                         = ProgramIdentifierBuilder::build();
        $user                            = UserIdentifierStub::buildGenericUser();
        $program_increment_configuration = ProgramIncrementTrackerConfiguration::fromProgram(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
                TrackerReferenceStub::withId(self::PROGRAM_INCREMENT_TRACKER_ID)
            ),
            RetrieveProgramIncrementLabelsStub::buildLabels(
                self::PROGRAM_INCREMENT_LABEL,
                self::PROGRAM_INCREMENT_SUB_LABEL
            ),
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            VerifyUserCanSubmitStub::userCanSubmit(),
            $program,
            $user
        );

        $iteration_configuration = IterationTrackerConfigurationBuilder::buildWithIdAndLabels(
            55,
            self::ITERATION_LABEL,
            'iteration'
        );

        $this->configuration = ProgramBacklogConfiguration::fromProgramIncrementAndIterationConfiguration(
            $program_increment_configuration,
            $iteration_configuration
        );
    }

    private function getPresenter(): ProgramBacklogPresenter
    {
        $project     = ProjectTestBuilder::aProject()
            ->withId(self::PROGRAM_ID)
            ->withAccess(\Project::ACCESS_PUBLIC)
            ->withPublicName(self::PROGRAM_LABEL)
            ->withUnixName(self::PROGRAM_SHORT_NAME)
            ->build();
        $first_flag  = new ProjectFlagPresenter('Secret', 'Confidential');
        $second_flag = new ProjectFlagPresenter('Blue', 'Blue category');

        return new ProgramBacklogPresenter($project, [$first_flag, $second_flag], true, $this->configuration, true);
    }

    public function testItBuildsFromValidProgramBacklogConfiguration(): void
    {
        $presenter = $this->getPresenter();
        self::assertTrue($presenter->is_configured);
        self::assertSame(self::PROGRAM_LABEL, $presenter->project_name);
        self::assertSame(self::PROGRAM_SHORT_NAME, $presenter->project_short_name);
        self::assertJson($presenter->project_privacy);
        self::assertJson($presenter->project_flags);
        self::assertSame(self::PROGRAM_ID, $presenter->program_id);
        self::assertTrue($presenter->user_has_accessibility_mode);
        self::assertTrue($presenter->has_plan_permissions);
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $presenter->program_increment_tracker_id);
        self::assertSame(self::PROGRAM_INCREMENT_LABEL, $presenter->program_increment_label);
        self::assertSame(self::PROGRAM_INCREMENT_SUB_LABEL, $presenter->program_increment_sub_label);
        self::assertTrue($presenter->is_iteration_tracker_defined);
        self::assertSame(self::ITERATION_LABEL, $presenter->iteration_label);
        self::assertTrue($presenter->is_program_admin);
        self::assertIsString($presenter->project_icon);
    }

    public function testItBuildsFromUnconfiguredProgram(): void
    {
        $this->configuration = ProgramBacklogConfiguration::buildForPotentialProgram();

        $presenter = $this->getPresenter();
        self::assertFalse($presenter->is_configured);
        self::assertNotSame('', $presenter->program_increment_label);
        self::assertNotSame('', $presenter->program_increment_sub_label);
        self::assertNotSame('', $presenter->iteration_label);
    }
}
