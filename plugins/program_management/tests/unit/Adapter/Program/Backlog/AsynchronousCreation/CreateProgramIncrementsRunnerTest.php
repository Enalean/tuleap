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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\PlanUserStoriesInMirroredProgramIncrements;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherFieldValuesStub;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFieldValuesGathererStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\Queue\WorkerEvent;

final class CreateProgramIncrementsRunnerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 18;
    private const USER_ID     = 120;
    private Stub|QueueFactory $queue_factory;
    private Stub|PendingArtifactCreationStore $pending_creation_store;
    private MockObject|TaskBuilder $task_builder;

    protected function setUp(): void
    {
        $this->queue_factory          = $this->createStub(QueueFactory::class);
        $this->pending_creation_store = $this->createStub(PendingArtifactCreationStore::class);
        $this->task_builder           = $this->createMock(TaskBuilder::class);
    }

    private function getRunner(): CreateProgramIncrementsRunner
    {
        $logger = new NullLogger();
        $task   = new CreateProgramIncrementsTask(
            RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(51),
            $this->createStub(ProgramIncrementsCreator::class),
            $logger,
            $this->pending_creation_store,
            $this->createStub(PlanUserStoriesInMirroredProgramIncrements::class),
            SearchTeamsOfProgramStub::buildTeams(163, 120),
            new BuildProjectStub(),
            GatherSynchronizedFieldsStub::withDefaults(),
            RetrieveFieldValuesGathererStub::withGatherer(GatherFieldValuesStub::withDefault()),
        );
        $this->task_builder->method('build')->willReturn($task);

        return new CreateProgramIncrementsRunner(
            $logger,
            $this->queue_factory,
            new ReplicationDataAdapter(
                $this->createStub(\Tracker_ArtifactFactory::class),
                $this->createStub(\UserManager::class),
                $this->pending_creation_store,
                $this->createStub(\Tracker_Artifact_ChangesetFactory::class),
                VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement()
            ),
            $this->task_builder
        );
    }

    public function testItExecuteMirrorsCreation(): void
    {
        $queue = $this->createMock(PersistentQueue::class);
        $this->queue_factory->method('getPersistentQueue')->willReturn($queue);

        $queue->expects(self::once())
            ->method('pushSinglePersistentMessage')
            ->with(
                'tuleap.program_management.program_increment.creation',
                ['artifact_id' => self::ARTIFACT_ID, 'user_id' => self::USER_ID]
            );

        $replication_data = ReplicationDataBuilder::buildWithArtifactIdAndUserId(self::ARTIFACT_ID, self::USER_ID);

        $this->getRunner()->executeProgramIncrementsCreation($replication_data);
    }

    public function testSkipsEventWhenReplicationDataDoesNotExist(): void
    {
        $event = new WorkerEvent(
            new NullLogger(),
            [
                'event_name' => 'tuleap.program_management.program_increment.creation',
                'payload'    => ['artifact_id' => self::ARTIFACT_ID, 'user_id' => self::USER_ID]
            ]
        );
        $this->pending_creation_store->method('getPendingArtifactById')->willReturn(null);

        $this->task_builder->expects(self::never())->method('build');
        $this->getRunner()->addListener($event);
    }
}
