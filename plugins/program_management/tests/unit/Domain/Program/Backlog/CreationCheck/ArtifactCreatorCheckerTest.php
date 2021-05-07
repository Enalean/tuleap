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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\PlanCheckException;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BuildPlanProgramIncrementConfiguration
     */
    private $build_plan_configuration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactCreatorChecker
     */
    private $milestone_creator_checker;

    /**
     * @var ArtifactCreatorChecker
     */
    private $artifact_creator_checker;

    protected function setUp(): void
    {
        $this->milestone_creator_checker = \Mockery::mock(ProgramIncrementArtifactCreatorChecker::class);
        $this->build_plan_configuration  = \Mockery::mock(BuildPlanProgramIncrementConfiguration::class);

        $this->artifact_creator_checker = new ArtifactCreatorChecker(
            $this->milestone_creator_checker,
            $this->build_plan_configuration
        );
    }

    public function testDisallowArtifactCreationWhenItIsAMilestoneTrackerAndMilestoneCannotBeCreated(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->build_plan_configuration->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn(new ProgramTracker($tracker));
        $this->milestone_creator_checker->shouldReceive('canProgramIncrementBeCreated')->once()->andReturn(false);

        self::assertFalse(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenProgramNotFound(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject(\Project::buildForTest())->build();

        $this->build_plan_configuration->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andThrow(new class extends \Exception implements PlanCheckException {
            });

        self::assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }

    public function testAllowArtifactCreationWhenTrackerDoesNotCreateMilestone(): void
    {
        $project = new Project(['group_id' => 105, 'unix_group_name' => "project", "group_name" => "Project"]);
        $tracker = TrackerTestBuilder::aTracker()->withId(102)->withProject($project)->build();
        $this->build_plan_configuration->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn(new ProgramTracker($tracker));
        $this->milestone_creator_checker->shouldReceive('canProgramIncrementBeCreated')->andReturn(true);

        self::assertTrue(
            $this->artifact_creator_checker->canCreateAnArtifact(
                UserTestBuilder::aUser()->build(),
                new ProgramTracker($tracker),
                ProjectAdapter::build($project)
            )
        );
    }
}
