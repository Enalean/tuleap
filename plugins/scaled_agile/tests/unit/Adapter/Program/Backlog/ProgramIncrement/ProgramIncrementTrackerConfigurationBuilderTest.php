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

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\ProgramIncrement;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\ProgramIncrementTrackerConfiguration;
use Tuleap\ScaledAgile\Program\Program;
use Tuleap\ScaledAgile\ScaledAgileTracker;
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

    protected function setUp(): void
    {
        $this->plan_builder = Mockery::mock(BuildPlanProgramIncrementConfiguration::class);

        $this->configuration_builder = new ProgramIncrementTrackerConfigurationBuilder(
            $this->plan_builder
        );
    }

    public function testItBuildsAProgramIncrementTrackerConfiguration(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->build();

        $scaled_agile_tracker = new ScaledAgileTracker($tracker);
        $this->plan_builder->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn($scaled_agile_tracker);

        $user                   = UserTestBuilder::aUser()->build();
        $project                = new Program(101);
        $expected_configuration = new ProgramIncrementTrackerConfiguration($project->getId(), false);

        self::assertEquals($expected_configuration, $this->configuration_builder->build($user, $project));
    }
}
