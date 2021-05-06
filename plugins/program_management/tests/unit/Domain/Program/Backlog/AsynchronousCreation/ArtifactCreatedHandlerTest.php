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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ArtifactCreatedHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|BuildPlanProgramIncrementConfiguration
     */
    private $build_plan_configuration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|RunProgramIncrementCreation
     */
    private $asyncronous_runner;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;

    /**
     * @var ArtifactCreatedHandler
     */
    private $handler;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProgramStore
     */
    private $program_store;

    protected function setUp(): void
    {
        $this->program_store                   = M::mock(ProgramStore::class);
        $this->build_plan_configuration        = M::mock(BuildPlanProgramIncrementConfiguration::class);
        $this->pending_artifact_creation_store = M::mock(PendingArtifactCreationStore::class);
        $this->asyncronous_runner              = M::mock(RunProgramIncrementCreation::class);
        $this->handler                         = new ArtifactCreatedHandler(
            $this->program_store,
            $this->asyncronous_runner,
            $this->pending_artifact_creation_store,
            $this->build_plan_configuration,
            new TestLogger()
        );
    }

    public function testHandleDelegatesToAsynchronousMirrorCreator(): void
    {
        $project = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $tracker = TrackerTestBuilder::aTracker()->withId(15)->withProject($project)->build();
        $this->program_store->shouldReceive('isProjectAProgramProject')->andReturnTrue();
        $current_user = UserTestBuilder::aUser()->withId(1001)->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->build_plan_configuration->shouldReceive('buildTrackerProgramIncrementFromProjectId')
            ->andReturn(new ProgramTracker($tracker));

        $this->pending_artifact_creation_store->shouldReceive('addArtifactToPendingCreation')
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

        $this->program_store->shouldReceive('isProjectAProgramProject')->with(101)->once()->andReturnFalse();

        $current_user = UserTestBuilder::aUser()->build();
        $artifact     = new Artifact(1, $tracker->getId(), $current_user->getId(), 12345678, false);
        $artifact->setTracker($tracker);
        $changeset = new \Tracker_Artifact_Changeset(21, $artifact, 36, 12345678, '');

        $this->handler->handle(new ArtifactCreated($artifact, $changeset, $current_user));

        $this->asyncronous_runner->shouldNotHaveReceived('executeMirrorsCreation');
    }
}
