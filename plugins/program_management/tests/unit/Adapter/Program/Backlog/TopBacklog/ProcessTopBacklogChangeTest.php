<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\ProgramForManagement;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;

final class ProcessTopBacklogChangeTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $permissions_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsExplicitTopBacklogDAO
     */
    private $dao;
    /**
     * @var ProcessTopBacklogChange
     */
    private $process_top_backlog_change;

    protected function setUp(): void
    {
        $this->artifact_factory     = \Mockery::mock(\Tracker_ArtifactFactory::class);
        $this->permissions_verifier = \Mockery::mock(PrioritizeFeaturesPermissionVerifier::class);
        $this->dao                  = \Mockery::mock(ArtifactsExplicitTopBacklogDAO::class);

        $this->process_top_backlog_change = new ProcessTopBacklogChange(
            $this->artifact_factory,
            $this->permissions_verifier,
            $this->dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testRemoveOnlyArtifactsUserCanView(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(Artifact::class);
        $tracker  = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(102);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 741)->andReturn($artifact);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->with($user, 789)->andReturn(null);

        $this->dao->shouldReceive('removeArtifactsFromExplicitTopBacklog')->with([741])->once();

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new ProgramForManagement(102),
            new TopBacklogChange([741, 789]),
            $user
        );
    }

    public function testRemoveOnlyArtifactThatArePartOfTheRequestedProgram(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(true);
        $user = UserTestBuilder::aUser()->build();

        $artifact = \Mockery::mock(Artifact::class);
        $tracker  = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(666);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactByIdUserCanView')->andReturn($artifact);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new ProgramForManagement(102),
            new TopBacklogChange([963]),
            $user
        );
    }

    public function testUserThatCannotPrioritizeFeaturesCannotAskForATopBacklogChange(): void
    {
        $this->permissions_verifier->shouldReceive('canUserPrioritizeFeatures')->andReturn(false);

        $this->dao->shouldNotReceive('removeArtifactsFromExplicitTopBacklog');

        $this->expectException(CannotManipulateTopBacklog::class);
        $this->process_top_backlog_change->processTopBacklogChangeForAProgram(
            new ProgramForManagement(102),
            new TopBacklogChange([403]),
            UserTestBuilder::aUser()->build()
        );
    }
}
