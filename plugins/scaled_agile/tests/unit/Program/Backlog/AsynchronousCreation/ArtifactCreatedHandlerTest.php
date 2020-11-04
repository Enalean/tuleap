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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|\PlanningFactory
     */
    private $planning_factory;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|CreateProgramIncrementsRunner
     */
    private $asyncronous_runner;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;

    /**
     * @var ArtifactCreatedHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProgramDao
     */
    private $program_dao;

    protected function setUp(): void
    {
        $this->program_dao                   = M::mock(ProgramDao::class);
        $this->planning_factory              = M::mock(\PlanningFactory::class);
        $planning_adapter                    = new PlanningAdapter($this->planning_factory);
        $this->pending_artifact_creation_dao = M::mock(PendingArtifactCreationDao::class);
        $this->asyncronous_runner            = M::mock(CreateProgramIncrementsRunner::class);
        $this->handler                       = new ArtifactCreatedHandler(
            $this->program_dao,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_dao,
            $planning_adapter
        );
    }

    public function testHandleDelegatesToAsynchronousMirrorCreator(): void
    {
        $project = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->withId(1001)->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $planning = new Planning(7, 'Irrelevant', $project->getID(), '', []);
        $planning->setPlanningTracker($tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $this->pending_artifact_creation_dao->shouldReceive('addArtifactToPendingCreation')
            ->withArgs([$artifact->getId(), $current_user->getId(), $changeset->getId()])
            ->once();

        $this->asyncronous_runner->shouldReceive('executeProgramIncrementsCreation')
            ->once();

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));
    }

    public function testHandleReactsOnlyToArtifactsFromProgramProjects(): void
    {
        $project = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();

        $this->program_dao->shouldReceive('isProjectAProgramProject')->with(101)->once()->andReturnFalse();

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleDoesNotReactWhenNoPlanningException(): void
    {
        $project = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();
        $this->planning_factory->shouldReceive('getRootPlanning')
            ->andThrow(new TopPlanningNotFoundInProjectException(102));

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }

    public function testHandleReactsOnlyToTopMilestones(): void
    {
        $project     = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $top_tracker = TrackerTestBuilder::aTracker()->withId(404)->withProject($project)->build();
        $this->program_dao->shouldReceive('isProjectAProgramProject')->andReturnTrue();

        $other_tracker = TrackerTestBuilder::aTracker()->withId(12)->withProject($project)->build();
        $planning      = new Planning(7, 'Irrelevant', $project->getID(), '', []);
        $planning->setPlanningTracker($other_tracker);
        $this->planning_factory->shouldReceive('getRootPlanning')->andReturn($planning);

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $top_tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($top_tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }
}
