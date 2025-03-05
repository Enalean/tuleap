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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationTrackerConfigurationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanSubmitStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramBacklogConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_INCREMENT_TRACKER_ID = 70;
    private const ITERATION_TRACKER_ID         = 80;
    private const PROGRAM_INCREMENT_LABEL      = 'Program Increments';
    private const PROGRAM_INCREMENT_SUB_LABEL  = 'program increment';
    private const ITERATION_LABEL              = 'Iterations';
    private ?IterationTrackerConfiguration $iteration_configuration;
    private ProgramIdentifier $program;
    private UserIdentifier $user;

    protected function setUp(): void
    {
        $this->program                 = ProgramIdentifierBuilder::build();
        $this->user                    = UserIdentifierStub::buildGenericUser();
        $this->iteration_configuration = IterationTrackerConfigurationBuilder::buildWithIdAndLabels(
            self::ITERATION_TRACKER_ID,
            self::ITERATION_LABEL,
            'iteration'
        );
    }

    private function getConfiguration(): ProgramBacklogConfiguration
    {
        return ProgramBacklogConfiguration::fromProgramIncrementAndIterationConfiguration(
            ProgramIncrementTrackerConfiguration::fromProgram(
                RetrieveVisibleProgramIncrementTrackerStub::withValidTracker(
                    TrackerReferenceStub::withId(self::PROGRAM_INCREMENT_TRACKER_ID)
                ),
                RetrieveProgramIncrementLabelsStub::buildLabels(
                    self::PROGRAM_INCREMENT_LABEL,
                    self::PROGRAM_INCREMENT_SUB_LABEL
                ),
                VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
                VerifyUserCanSubmitStub::userCanSubmit(),
                $this->program,
                $this->user
            ),
            $this->iteration_configuration
        );
    }

    public function testItBuildsFromProgramIncrementAndIterationConfiguration(): void
    {
        $configuration = $this->getConfiguration();
        self::assertTrue($configuration->is_configured);
        self::assertTrue($configuration->can_create_program);
        self::assertTrue($configuration->has_plan_permissions);
        self::assertSame(self::PROGRAM_INCREMENT_TRACKER_ID, $configuration->program_increment_tracker_id);
        self::assertSame(self::PROGRAM_INCREMENT_LABEL, $configuration->program_increment_label);
        self::assertSame(self::PROGRAM_INCREMENT_SUB_LABEL, $configuration->program_increment_sublabel);
        self::assertTrue($configuration->is_iteration_tracker_defined);
        self::assertSame(self::ITERATION_LABEL, $configuration->iteration_label);
    }

    public function testItBuildsWithoutIterationConfiguration(): void
    {
        $this->iteration_configuration = null;
        $configuration                 = $this->getConfiguration();
        self::assertFalse($configuration->is_iteration_tracker_defined);
        self::assertSame('', $configuration->iteration_label);
    }

    public function testItBuildsEmptyPresenterForUnconfiguredProgram(): void
    {
        $configuration = ProgramBacklogConfiguration::buildForPotentialProgram();
        self::assertFalse($configuration->is_configured);
        self::assertFalse($configuration->can_create_program);
        self::assertFalse($configuration->has_plan_permissions);
        self::assertSame(0, $configuration->program_increment_tracker_id);
        self::assertSame('', $configuration->program_increment_label);
        self::assertSame('', $configuration->program_increment_sublabel);
        self::assertFalse($configuration->is_iteration_tracker_defined);
        self::assertSame('', $configuration->iteration_label);
    }
}
