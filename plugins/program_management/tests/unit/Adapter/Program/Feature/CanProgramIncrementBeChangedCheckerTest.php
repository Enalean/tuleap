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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanProgramIncrementConfigurationBuilder;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanProgramIncrementBeChangedCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TrackerShouldPlanFeatureChecker
     */
    private $checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PlanProgramIncrementConfigurationBuilder
     */
    private $configuration_builder;

    protected function setUp(): void
    {
        $this->configuration_builder = \Mockery::mock(PlanProgramIncrementConfigurationBuilder::class);
        $this->checker               = new TrackerShouldPlanFeatureChecker($this->configuration_builder);
    }

    public function testItReturnsFalseWhenConfigurationThrowError(): void
    {
        $artifact = \Mockery::mock(Artifact::class);

        $user = UserTestBuilder::aUser()->build();

        $project = new \Project(['group_id' => 101]);
        $event   = new ArtifactUpdated($artifact, $user, $project);
        $this->configuration_builder->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andThrow(new ProgramTrackerNotFoundException(1));

        self::assertEquals(false, $this->checker->checkTrackerCanPlanFeature($event));
    }

    public function testItReturnsFalseWhenTrackerIsNotProgramIncrement(): void
    {
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTrackerId')->once()->andReturn(1);

        $user = UserTestBuilder::aUser()->build();

        $project = new \Project(['group_id' => 101]);
        $event   = new ArtifactUpdated($artifact, $user, $project);
        $this->configuration_builder->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn(new ProgramTracker(TrackerTestBuilder::aTracker()->withId(2)->build()));

        self::assertEquals(false, $this->checker->checkTrackerCanPlanFeature($event));
    }

    public function testItReturnsTrueWhenTrackerIsProgramIncrement(): void
    {
        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getTrackerId')->once()->andReturn(1);

        $user = UserTestBuilder::aUser()->build();

        $project = new \Project(['group_id' => 101]);
        $event   = new ArtifactUpdated($artifact, $user, $project);
        $this->configuration_builder->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn(new ProgramTracker(TrackerTestBuilder::aTracker()->withId(1)->build()));

        self::assertEquals(true, $this->checker->checkTrackerCanPlanFeature($event));
    }
}
