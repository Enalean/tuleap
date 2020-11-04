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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactChangesetNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactCreationDao;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\PendingArtifactUserNotFoundException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

final class ReplicationDataAdapterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ReplicationDataAdapter
     */
    private $adapter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PendingArtifactCreationDao
     */
    private $pending_artifact_creation_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->artifact_factory              = Mockery::mock(Tracker_ArtifactFactory::class);
        $this->user_manager                  = Mockery::mock(UserManager::class);
        $this->pending_artifact_creation_dao = Mockery::mock(PendingArtifactCreationDao::class);
        $this->changeset_factory             = Mockery::mock(Tracker_Artifact_ChangesetFactory::class);

        $this->adapter = new ReplicationDataAdapter(
            $this->artifact_factory,
            $this->user_manager,
            $this->pending_artifact_creation_dao,
            $this->changeset_factory
        );
    }

    public function testItThrowErrorWhenPendingArtifactIsNotFoundInDB(): void
    {
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')->once()->andReturnNull();

        $this->expectException(PendingArtifactNotFoundException::class);

        $this->adapter->buildFromArtifactAndUserId(1, 101);
    }

    public function testItThrowErrorWhenPendingArtifactIsNotFound(): void
    {
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->andReturn(['program_artifact_id' => 1, 'user_id' => 101, 'changeset_id' => 666]);

        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->once()->andReturnNull();

        $this->expectException(PendingArtifactNotFoundException::class);

        $this->adapter->buildFromArtifactAndUserId(1, 101);
    }

    public function testItThrowsWhenUserIsNotFound(): void
    {
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->andReturn(['program_artifact_id' => 1, 'user_id' => 101, 'changeset_id' => 666]);

        $artifact = new Artifact(1, 10, 101, 123456789, true);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->once()->andReturn($artifact);

        $this->user_manager->shouldReceive('getUserById')->once()->andReturnNull();

        $this->expectException(PendingArtifactUserNotFoundException::class);

        $this->adapter->buildFromArtifactAndUserId(1, 101);
    }

    public function testItThrowsWhenChangesetIsNotFound(): void
    {
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->andReturn(['program_artifact_id' => 1, 'user_id' => 101, 'changeset_id' => 666]);

        $artifact = new Artifact(1, 10, 101, 123456789, true);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->once()->andReturn($artifact);

        $user = UserTestBuilder::aUser()->withId(101)->build();
        $this->user_manager->shouldReceive('getUserById')->once()->andReturn($user);

        $this->changeset_factory->shouldReceive('getChangeset')->once()->with($artifact, 666)->andReturnNull();

        $this->expectException(PendingArtifactChangesetNotFoundException::class);

        $this->adapter->buildFromArtifactAndUserId(1, 101);
    }

    public function testItBuilsReplicationData(): void
    {
        $this->pending_artifact_creation_dao->shouldReceive('getPendingArtifactById')
            ->once()
            ->andReturn(['program_artifact_id' => 1, 'user_id' => 101, 'changeset_id' => 666]);

        $artifact = new Artifact(1, 10, 101, 123456789, true);
        $this->artifact_factory->shouldReceive('getArtifactById')->with(1)->once()->andReturn($artifact);

        $project = new \Project(['group_id' => 101, 'unix_group_name' => 'project', 'group_name' => 'My project']);
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();
        $artifact->setTracker($tracker);

        $user = UserTestBuilder::aUser()->withId(101)->build();
        $this->user_manager->shouldReceive('getUserById')->once()->andReturn($user);

        $changeset = new \Tracker_Artifact_Changeset(666, $artifact, $user->getId(), 123456789, "user@example.com");
        $this->changeset_factory->shouldReceive('getChangeset')->once()->with($artifact, 666)->andReturn($changeset);

        $this->adapter->buildFromArtifactAndUserId(1, 101);
    }
}
