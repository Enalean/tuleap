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

use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Queue\PersistentQueue;
use Tuleap\Queue\QueueFactory;
use Tuleap\ScaledAgile\Adapter\Program\ReplicationDataAdapter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

final class CreateProgramIncrementsRunnerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CreateProgramIncrementsRunner
     */
    private $runner;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|QueueFactory
     */
    private $queue_factory;

    protected function setUp(): void
    {
        $logger              = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->queue_factory = Mockery::mock(QueueFactory::class);
        $replication_adapter = new ReplicationDataAdapter(
            Mockery::mock(Tracker_ArtifactFactory::class),
            Mockery::mock(UserManager::class),
            Mockery::mock(PendingArtifactCreationDao::class),
            Mockery::mock(Tracker_Artifact_ChangesetFactory::class)
        );
        $this->runner        = new CreateProgramIncrementsRunner(
            $logger,
            $this->queue_factory,
            $replication_adapter
        );
    }

    public function testItExecuteMirrorsCreation(): void
    {
        $project  = new Project(['group_id' => 123, 'group_name' => 'Project', 'unix_group_name' => 'project']);
        $tracker  = TrackerTestBuilder::aTracker()->withId(102)->withProject($project)->build();
        $user     = UserTestBuilder::aUser()->withId(10)->build();
        $artifact = new Artifact(1, 10, $user->getId(), 123456789, true);
        $artifact->setTracker($tracker);

        $changeset = new Tracker_Artifact_Changeset(
            1,
            $artifact,
            $user->getId(),
            12345678,
            "usermail@example.com"
        );

        $queue = \Mockery::mock(PersistentQueue::class);
        $this->queue_factory->shouldReceive('getPersistentQueue')->andReturn($queue);

        $queue->shouldReceive('pushSinglePersistentMessage')
            ->withArgs(
                ['tuleap.tracker.artifact.creation', ['artifact_id' => $artifact->getId(), 'user_id' => $user->getId()]]
            )
            ->once();

        $replication_data = ReplicationDataAdapter::build($artifact, $user, $changeset);

        $this->runner->executeProgramIncrementsCreation($replication_data);
    }
}
