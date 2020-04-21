<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use PlanningFactory;
use Project;
use Tracker_Artifact;
use Tracker_ArtifactFactory;

class TopBacklogElementsToAddCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var TopBacklogElementsToAddChecker
     */
    private $checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlanningFactory
     */
    private $planning_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Planning
     */
    private $root_planning;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_201;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact_202;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planning_factory = Mockery::mock(PlanningFactory::class);
        $this->artifact_factory = Mockery::mock(Tracker_ArtifactFactory::class);

        $this->checker = new TopBacklogElementsToAddChecker(
            $this->planning_factory,
            $this->artifact_factory
        );

        $this->project = Mockery::mock(Project::class);
        $this->user    = Mockery::mock(PFUser::class);

        $this->project->shouldReceive('getID')->andReturn('101');

        $this->root_planning = Mockery::mock(Planning::class);
        $this->root_planning->shouldReceive('getBacklogTrackersIds')->andReturn([101, 104]);

        $this->artifact_201 = Mockery::mock(Tracker_Artifact::class);
        $this->artifact_202 = Mockery::mock(Tracker_Artifact::class);
    }

    public function testItDoesNotThrowExceptionIfArtifactsAreInTopBacklogTracker(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(201)
            ->andReturn($this->artifact_201);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(202)
            ->andReturn($this->artifact_202);

        $this->artifact_201->shouldReceive('getTrackerId')->andReturn('104');
        $this->artifact_202->shouldReceive('getTrackerId')->andReturn('101');

        $added_artifact_ids = [
            201,
            202
        ];

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            $added_artifact_ids
        );

        // This asserts that there are no exception thrown
        $this->assertTrue(true);
    }

    public function testItThrowsAnExceptionIfNoRootPlanning(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 101)
            ->andReturnNull();

        $this->expectException(NoRootPlanningException::class);

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            []
        );
    }

    public function testItThrowsAnExceptionIfAtLeastOneArtifactIsNotInTopBacklogTracker(): void
    {
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->with($this->user, 101)
            ->andReturn($this->root_planning);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(201)
            ->andReturn($this->artifact_201);

        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with(202)
            ->andReturn($this->artifact_202);

        $this->artifact_201->shouldReceive('getTrackerId')->andReturn('999');
        $this->artifact_202->shouldReceive('getTrackerId')->andReturn('101');

        $this->expectException(ProvidedAddedIdIsNotInPartOfTopBacklogException::class);

        $added_artifact_ids = [
            201,
            202
        ];

        $this->checker->checkAddedIdsBelongToTheProjectTopBacklogTrackers(
            $this->project,
            $this->user,
            $added_artifact_ids
        );
    }
}
