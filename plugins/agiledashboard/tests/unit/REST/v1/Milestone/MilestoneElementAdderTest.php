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

namespace Tuleap\AgileDashboard\REST\v1\Milestone;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\UnplannedArtifactsAdder;
use Tuleap\AgileDashboard\Milestone\Backlog\NoRootPlanningException;
use Tuleap\AgileDashboard\Milestone\Backlog\ProvidedAddedIdIsNotInPartOfTopBacklogException;
use Tuleap\AgileDashboard\Milestone\Backlog\TopBacklogElementsToAddChecker;
use Tuleap\AgileDashboard\REST\v1\BacklogAddRepresentation;
use Tuleap\AgileDashboard\REST\v1\ResourcesPatcher;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class MilestoneElementAdderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DBTransactionExecutorWithConnection
     */
    private $transaction_executor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var MilestoneElementAdder
     */
    private $adder;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ResourcesPatcher
     */
    private $resources_patcher;

    /**
     * @var BacklogAddRepresentation
     */
    private $backlog_add_representation;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TopBacklogElementsToAddChecker
     */
    private $top_backlog_elements_to_add_checker;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UnplannedArtifactsAdder
     */
    private $unplanned_artifact_adder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resources_patcher                   = Mockery::mock(ResourcesPatcher::class);
        $this->explicit_backlog_dao                = Mockery::mock(ExplicitBacklogDao::class);
        $this->unplanned_artifact_adder            = Mockery::mock(UnplannedArtifactsAdder::class);
        $this->top_backlog_elements_to_add_checker = Mockery::mock(TopBacklogElementsToAddChecker::class);

        $this->transaction_executor = new DBTransactionExecutorPassthrough();

        $this->adder = new MilestoneElementAdder(
            $this->explicit_backlog_dao,
            $this->unplanned_artifact_adder,
            $this->resources_patcher,
            $this->top_backlog_elements_to_add_checker,
            $this->transaction_executor
        );

        $this->backlog_add_representation = new BacklogAddRepresentation();
        $this->backlog_add_representation->id = 112;

        $this->artifact = Mockery::mock(Tracker_Artifact::class);
    }

    public function testItAddsElementToMilestoneInExplicitMode(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [$this->backlog_add_representation];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);

        $this->artifact->shouldReceive('getTrackerId')->andReturn(101);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->once();

        $this->unplanned_artifact_adder->shouldReceive('addArtifactToTopBacklogFromIds')->once();
        $this->resources_patcher->shouldReceive('removeArtifactFromSource')->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->withArgs([102])
            ->andReturnTrue();

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItAddsElementToMilestoneInStandardMode(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [$this->backlog_add_representation];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);

        $this->artifact->shouldReceive('getTrackerId')->andReturn(101);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->once();

        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')
            ->once()
            ->withArgs([102])
            ->andReturnFalse();

        $this->resources_patcher->shouldReceive('removeArtifactFromSource')
            ->once()
            ->withArgs([$user, $add]);

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItDoesNotAddElementToMilestoneIfAtLeastOneArtifactIsNotInTopBacklogTracker(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [$this->backlog_add_representation];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);

        $this->artifact->shouldReceive('getTrackerId')->andReturn(199);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->once()
            ->andThrow(new ProvidedAddedIdIsNotInPartOfTopBacklogException([]));

        $this->unplanned_artifact_adder->shouldReceive('addArtifactToTopBacklogFromIds')->never();
        $this->resources_patcher->shouldReceive('removeArtifactFromSource')->never();
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->never();

        $this->expectException(ProvidedAddedIdIsNotInPartOfTopBacklogException::class);

        $this->adder->addElementToBacklog($project, $add, $user);
    }

    public function testItDoesNotAddElementToMilestoneIfNoRootPlanning(): void
    {
        $user    = Mockery::mock(\PFUser::class);
        $add     = [$this->backlog_add_representation];
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn(102);

        $this->artifact->shouldReceive('getTrackerId')->andReturn(199);

        $this->top_backlog_elements_to_add_checker->shouldReceive('checkAddedIdsBelongToTheProjectTopBacklogTrackers')
            ->once()
            ->andThrow(new NoRootPlanningException());

        $this->unplanned_artifact_adder->shouldReceive('addArtifactToTopBacklogFromIds')->never();
        $this->resources_patcher->shouldReceive('removeArtifactFromSource')->never();
        $this->explicit_backlog_dao->shouldReceive('isProjectUsingExplicitBacklog')->never();

        $this->expectException(NoRootPlanningException::class);

        $this->adder->addElementToBacklog($project, $add, $user);
    }
}
