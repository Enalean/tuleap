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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementTrackerConfigurationBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ProgramIncrementTrackerConfigurationBuilder
     */
    private $configuration_builder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BuildPlanProgramIncrementConfiguration
     */
    private $plan_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanStore
     */
    private $plan_store;

    protected function setUp(): void
    {
        $this->plan_builder = Mockery::mock(BuildPlanProgramIncrementConfiguration::class);
        $this->plan_store   = Mockery::mock(PlanStore::class);

        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $program_tracker = new ProgramTracker($tracker);
        $this->plan_builder
            ->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn($program_tracker)
            ->byDefault();

        $this->configuration_builder = new ProgramIncrementTrackerConfigurationBuilder(
            $this->plan_builder,
            $this->plan_store
        );
    }

    public function testItBuildsAProgramIncrementTrackerConfiguration(): void
    {
        $this->plan_store
            ->shouldReceive("getProgramIncrementLabels")
            ->andReturn(['label' => "Program Increments", 'sub_label' => "program increment"])
            ->once();

        $user                   = UserTestBuilder::aUser()->build();
        $project                = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $expected_configuration = new ProgramIncrementTrackerConfiguration(
            $project->getId(),
            false,
            "Program Increments",
            "program increment"
        );

        self::assertEquals($expected_configuration, $this->configuration_builder->build($user, $project));
    }

    public function testItBuildsAProgramIncrementTrackerConfigurationWithNullLabel(): void
    {
        $this->plan_store
            ->shouldReceive("getProgramIncrementLabels")
            ->andReturnNull()
            ->once();

        $user                   = UserTestBuilder::aUser()->build();
        $project                = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);
        $expected_configuration = new ProgramIncrementTrackerConfiguration(
            $project->getId(),
            false,
            null,
            null
        );

        self::assertEquals($expected_configuration, $this->configuration_builder->build($user, $project));
    }
}
