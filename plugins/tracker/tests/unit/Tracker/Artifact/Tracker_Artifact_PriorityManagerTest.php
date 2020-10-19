<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_Artifact_PriorityManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Tracker_Artifact_PriorityManager */
    private $priority_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|Tracker_Artifact_PriorityDao
     */
    private $priority_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|Tracker_Artifact_PriorityHistoryDao
     */
    private $priority_history_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->priority_dao         = M::mock(Tracker_Artifact_PriorityDao::class);
        $this->priority_history_dao = M::mock(Tracker_Artifact_PriorityHistoryDao::class);
        $this->user_manager         = M::mock(UserManager::class);
        $this->artifact_factory     = M::mock(Tracker_ArtifactFactory::class);
        $this->priority_manager     = new Tracker_Artifact_PriorityManager(
            $this->priority_dao,
            $this->priority_history_dao,
            $this->user_manager,
            $this->artifact_factory
        );
    }

    public function testEnableExceptionsOnErrorProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('enableExceptionsOnError')->once();
        $this->priority_manager->enableExceptionsOnError();
    }

    public function testStartTransactionProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('startTransaction')->once();
        $this->priority_manager->startTransaction();
    }

    public function testCommitProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('commit')->once();
        $this->priority_manager->commit();
    }

    public function testRollbackProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('rollBack')->once();
        $this->priority_manager->rollback();
    }

    public function testRemoveProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('remove')
            ->with(58)
            ->once();
        $this->priority_manager->remove(58);
    }

    public function testGetGlobalRankReturnsInt(): void
    {
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(58)
            ->once()
            ->andReturn(1994);
        $this->assertEquals(1994, $this->priority_manager->getGlobalRank(58));
    }

    public function testGetGlobalRankReturnsNull(): void
    {
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(0)
            ->once()
            ->andReturnNull();
        $this->assertNull($this->priority_manager->getGlobalRank(0));
    }

    public function testMoveArtifactAfterProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('moveArtifactAfter')
            ->with(58, 123)
            ->once();
        $this->priority_manager->moveArtifactAfter(58, 123);
    }

    public function testMoveArtifactAfterWithHistoryChangeLogging(): void
    {
        $this->priority_dao->shouldReceive('moveArtifactAfter')
            ->with(58, 123)
            ->once();
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(58)
            ->twice()
            ->andReturns(1000, 1042);
        $this->mockMovedArtifact(58, true);
        $this->user_manager->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn(M::mock(PFUser::class)->shouldReceive(['getId' => 111])->getMock());
        $this->priority_history_dao->shouldReceive('logPriorityChange')
            ->once();

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    public function testMoveArtifactAfterWithHistoryChangeLoggingDoesNotLogWhenRankHasNotChanged(): void
    {
        $this->priority_dao->shouldReceive('moveArtifactAfter')
            ->with(58, 123)
            ->once();
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(58)
            ->twice()
            ->andReturns(1042, 1042);
        $this->priority_history_dao->shouldNotReceive('logPriorityChange');

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    public function testMoveArtifactAfterWithHistoryChangeLoggingDoesNotLogWhenTrackerDoesNotShowPriorityChanges(): void
    {
        $this->priority_dao->shouldReceive('moveArtifactAfter')
            ->with(58, 123)
            ->once();
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(58)
            ->twice()
            ->andReturns(1000, 1042);
        $this->mockMovedArtifact(58, false);
        $this->priority_history_dao->shouldNotReceive('logPriorityChange');

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    private function mockMovedArtifact(int $artifact_id, bool $are_priority_changes_shown)
    {
        $artifact = M::mock(Artifact::class);
        $tracker  = M::mock(Tracker::class);
        $tracker->shouldReceive('arePriorityChangesShown')
            ->once()
            ->andReturn($are_priority_changes_shown);
        $artifact->shouldReceive('getTracker')
            ->once()
            ->andReturn($tracker);
        $this->artifact_factory->shouldReceive('getArtifactById')
            ->with($artifact_id)
            ->once()
            ->andReturn($artifact);
    }

    public function testMoveListOfArtifactsBefore(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->shouldReceive('getGlobalRanks')
            ->with($artifact_ids)
            ->once()
            ->andReturn(
                [
                    ['artifact_id' => 123, 'rank' => 1000],
                    ['artifact_id' => 789, 'rank' => 1042]
                ]
            );
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(123)
            ->once()
            ->andReturn(2040);
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(789)
            ->once()
            ->andReturn(2041);
        $this->priority_dao->shouldReceive('moveListOfArtifactsBefore')
            ->with($artifact_ids, 456)
            ->once();
        $this->mockMovedArtifact(123, true);
        $this->mockMovedArtifact(789, true);
        $this->priority_history_dao->shouldReceive('logPriorityChange')
            ->twice();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->twice()
            ->andReturn(M::mock(PFUser::class)->shouldReceive(['getId' => 111])->getMock());

        $this->priority_manager->moveListOfArtifactsBefore($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsBeforeDoesNotLogWhenRankHasNotChanged(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->shouldReceive('getGlobalRanks')
            ->with($artifact_ids)
            ->once()
            ->andReturn(
                [
                    ['artifact_id' => 123, 'rank' => 2040],
                    ['artifact_id' => 789, 'rank' => 2041]
                ]
            );
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(123)
            ->once()
            ->andReturn(2040);
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(789)
            ->once()
            ->andReturn(2041);
        $this->priority_dao->shouldReceive('moveListOfArtifactsBefore')
            ->with($artifact_ids, 456)
            ->once();
        $this->priority_history_dao->shouldNotReceive('logPriorityChange');

        $this->priority_manager->moveListOfArtifactsBefore($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsAfter(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->shouldReceive('getGlobalRanks')
            ->with($artifact_ids)
            ->once()
            ->andReturn(
                [
                    ['artifact_id' => 123, 'rank' => 1000],
                    ['artifact_id' => 789, 'rank' => 1042]
                ]
            );
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(123)
            ->once()
            ->andReturn(2040);
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(789)
            ->once()
            ->andReturn(2041);
        $this->priority_dao->shouldReceive('moveListOfArtifactsAfter')
            ->with($artifact_ids, 456)
            ->once();
        $this->mockMovedArtifact(123, true);
        $this->mockMovedArtifact(789, true);
        $this->priority_history_dao->shouldReceive('logPriorityChange')
            ->twice();
        $this->user_manager->shouldReceive('getCurrentUser')
            ->twice()
            ->andReturn(M::mock(PFUser::class)->shouldReceive(['getId' => 111])->getMock());

        $this->priority_manager->moveListOfArtifactsAfter($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsAfterDoesNotLogWhenRankHasNotChanged(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->shouldReceive('getGlobalRanks')
            ->with($artifact_ids)
            ->once()
            ->andReturn(
                [
                    ['artifact_id' => 123, 'rank' => 2040],
                    ['artifact_id' => 789, 'rank' => 2041]
                ]
            );
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(123)
            ->once()
            ->andReturn(2040);
        $this->priority_dao->shouldReceive('getGlobalRank')
            ->with(789)
            ->once()
            ->andReturn(2041);
        $this->priority_dao->shouldReceive('moveListOfArtifactsAfter')
            ->with($artifact_ids, 456)
            ->once();
        $this->priority_history_dao->shouldNotReceive('logPriorityChange');

        $this->priority_manager->moveListOfArtifactsAfter($artifact_ids, 456, 847, 102);
    }

    public function testDeletePriorityReturnsTrueWhenHistoryIsUpdated(): void
    {
        $this->priority_dao->shouldReceive('remove')
            ->with(58)
            ->once()
            ->andReturnTrue();
        $this->priority_history_dao->shouldReceive('deletePriorityChangesHistory')
            ->with(58)
            ->once()
            ->andReturnTrue();
        $this->assertTrue(
            $this->priority_manager->deletePriority(
                M::mock(Artifact::class)->shouldReceive(['getId' => 58])->getMock()
            )
        );
    }

    public function testDeletePriorityReturnsFalseWhenHistoryIsNotUpdated(): void
    {
        $this->priority_dao->shouldReceive('remove')
            ->with(58)
            ->once()
            ->andReturnTrue();
        $this->priority_history_dao->shouldReceive('deletePriorityChangesHistory')
            ->with(58)
            ->once()
            ->andReturnFalse();
        $this->assertFalse(
            $this->priority_manager->deletePriority(
                M::mock(Artifact::class)->shouldReceive(['getId' => 58])->getMock()
            )
        );
    }

    public function testPutArtifactAtAGivenRankProxiesToDao(): void
    {
        $this->priority_dao->shouldReceive('putArtifactAtAGivenRank')
            ->with(58, 1042)
            ->once();
        $this->priority_manager->putArtifactAtAGivenRank(
            M::mock(Artifact::class)->shouldReceive(['getId' => 58])->getMock(),
            1042
        );
    }
}
