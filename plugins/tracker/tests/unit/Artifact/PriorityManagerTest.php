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

namespace Tuleap\Tracker\Artifact;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_PriorityHistoryDao;
use Tracker_ArtifactFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Dao\PriorityDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class PriorityManagerTest extends TestCase
{
    private PriorityManager $priority_manager;
    private PriorityDao&MockObject $priority_dao;
    private Tracker_Artifact_PriorityHistoryDao&MockObject $priority_history_dao;
    private UserManager&MockObject $user_manager;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private EasyDB&MockObject $db;

    protected function setUp(): void
    {
        $this->priority_dao         = $this->createMock(PriorityDao::class);
        $this->priority_history_dao = $this->createMock(Tracker_Artifact_PriorityHistoryDao::class);
        $this->user_manager         = $this->createMock(UserManager::class);
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->db                   = $this->createMock(EasyDB::class);
        $this->priority_manager     = new PriorityManager(
            $this->priority_dao,
            $this->priority_history_dao,
            $this->user_manager,
            $this->artifact_factory,
            $this->db,
        );
    }

    public function testStartTransactionProxiesToDao(): void
    {
        $this->db->expects($this->once())->method('beginTransaction');
        $this->priority_manager->startTransaction();
    }

    public function testCommitProxiesToDao(): void
    {
        $this->db->expects($this->once())->method('commit');
        $this->priority_manager->commit();
    }

    public function testRollbackProxiesToDao(): void
    {
        $this->db->expects($this->once())->method('rollBack');
        $this->priority_manager->rollback();
    }

    public function testGetGlobalRankReturnsInt(): void
    {
        $this->priority_dao->expects($this->once())->method('getGlobalRank')->with(58)->willReturn(1994);
        self::assertEquals(1994, $this->priority_manager->getGlobalRank(58));
    }

    public function testGetGlobalRankReturnsNull(): void
    {
        $this->priority_dao->expects($this->once())->method('getGlobalRank')->with(0)->willReturn(null);
        self::assertNull($this->priority_manager->getGlobalRank(0));
    }

    public function testMoveArtifactAfterProxiesToDao(): void
    {
        $this->priority_dao->expects($this->once())->method('moveArtifactAfter')->with(58, 123);
        $this->priority_manager->moveArtifactAfter(58, 123);
    }

    public function testMoveArtifactAfterWithHistoryChangeLogging(): void
    {
        $this->priority_dao->expects($this->once())->method('moveArtifactAfter')->with(58, 123);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->with(58)->willReturnOnConsecutiveCalls(1000, 1042);
        $this->artifact_factory->method('getArtifactById')->with(58)->willReturn($this->mockMovedArtifact(58, true));
        $this->user_manager->expects($this->once())->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithId(111));
        $this->priority_history_dao->expects($this->once())->method('logPriorityChange');

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    public function testMoveArtifactAfterWithHistoryChangeLoggingDoesNotLogWhenRankHasNotChanged(): void
    {
        $this->priority_dao->expects($this->once())->method('moveArtifactAfter')->with(58, 123);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->with(58)->willReturn(1042);
        $this->priority_history_dao->expects($this->never())->method('logPriorityChange');

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    public function testMoveArtifactAfterWithHistoryChangeLoggingDoesNotLogWhenTrackerDoesNotShowPriorityChanges(): void
    {
        $this->priority_dao->expects($this->once())->method('moveArtifactAfter')->with(58, 123);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->with(58)->willReturnOnConsecutiveCalls(1000, 1042);
        $this->artifact_factory->method('getArtifactById')->with(58)->willReturn($this->mockMovedArtifact(58, false));
        $this->priority_history_dao->expects($this->never())->method('logPriorityChange');

        $this->priority_manager->moveArtifactAfterWithHistoryChangeLogging(58, 123, 847, 102);
    }

    private function mockMovedArtifact(int $artifact_id, bool $are_priority_changes_shown): Artifact
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->inTracker($tracker)->build();
        $tracker->expects($this->once())->method('arePriorityChangesShown')->willReturn($are_priority_changes_shown);
        return $artifact;
    }

    public function testMoveListOfArtifactsBefore(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->expects($this->once())->method('getGlobalRanks')
            ->with($artifact_ids)
            ->willReturn([
                ['artifact_id' => 123, 'rank' => 1000],
                ['artifact_id' => 789, 'rank' => 1042],
            ]);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->willReturnCallback(static fn(int $id) => match ($id) {
            123 => 2040,
            789 => 2041,
        });
        $this->priority_dao->expects($this->once())->method('moveListOfArtifactsBefore')->with($artifact_ids, 456);
        $this->artifact_factory->method('getArtifactById')->willReturnCallback(fn(int $id) => match ($id) {
            123 => $this->mockMovedArtifact(123, true),
            789 => $this->mockMovedArtifact(789, true)
        });
        $this->priority_history_dao->expects($this->exactly(2))->method('logPriorityChange');
        $this->user_manager->expects($this->exactly(2))->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithId(111));

        $this->priority_manager->moveListOfArtifactsBefore($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsBeforeDoesNotLogWhenRankHasNotChanged(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->expects($this->once())->method('getGlobalRanks')
            ->with($artifact_ids)
            ->willReturn([
                ['artifact_id' => 123, 'rank' => 2040],
                ['artifact_id' => 789, 'rank' => 2041],
            ]);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->willReturnCallback(static fn(int $id) => match ($id) {
            123 => 2040,
            789 => 2041,
        });
        $this->priority_dao->expects($this->once())->method('moveListOfArtifactsBefore')->with($artifact_ids, 456);
        $this->priority_history_dao->expects($this->never())->method('logPriorityChange');

        $this->priority_manager->moveListOfArtifactsBefore($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsAfter(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->expects($this->once())->method('getGlobalRanks')
            ->with($artifact_ids)
            ->willReturn([
                ['artifact_id' => 123, 'rank' => 1000],
                ['artifact_id' => 789, 'rank' => 1042],
            ]);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->willReturnCallback(static fn(int $id) => match ($id) {
            123 => 2040,
            789 => 2041,
        });
        $this->priority_dao->expects($this->once())->method('moveListOfArtifactsAfter')->with($artifact_ids, 456);
        $this->artifact_factory->method('getArtifactById')->willReturnCallback(fn(int $id) => match ($id) {
            123 => $this->mockMovedArtifact(123, true),
            789 => $this->mockMovedArtifact(789, true),
        });
        $this->priority_history_dao->expects($this->exactly(2))->method('logPriorityChange');
        $this->user_manager->expects($this->exactly(2))->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithId(111));

        $this->priority_manager->moveListOfArtifactsAfter($artifact_ids, 456, 847, 102);
    }

    public function testMoveListOfArtifactsAfterDoesNotLogWhenRankHasNotChanged(): void
    {
        $artifact_ids = [123, 789];
        $this->priority_dao->expects($this->once())->method('getGlobalRanks')
            ->with($artifact_ids)
            ->willReturn([
                ['artifact_id' => 123, 'rank' => 2040],
                ['artifact_id' => 789, 'rank' => 2041],
            ]);
        $this->priority_dao->expects($this->exactly(2))->method('getGlobalRank')->willReturnCallback(static fn(int $id) => match ($id) {
            123 => 2040,
            789 => 2041,
        });
        $this->priority_dao->expects($this->once())->method('moveListOfArtifactsAfter')->with($artifact_ids, 456);
        $this->priority_history_dao->expects($this->never())->method('logPriorityChange');

        $this->priority_manager->moveListOfArtifactsAfter($artifact_ids, 456, 847, 102);
    }

    public function testDeletePriorityReturnsTrueWhenHistoryIsUpdated(): void
    {
        $this->priority_dao->expects($this->once())->method('remove')->with(58)->willReturn(true);
        $this->priority_history_dao->expects($this->once())->method('deletePriorityChangesHistory')->with(58)->willReturn(true);
        self::assertTrue($this->priority_manager->deletePriority(ArtifactTestBuilder::anArtifact(58)->build()));
    }

    public function testDeletePriorityReturnsFalseWhenHistoryIsNotUpdated(): void
    {
        $this->priority_dao->expects($this->once())->method('remove')->with(58)->willReturn(true);
        $this->priority_history_dao->expects($this->once())->method('deletePriorityChangesHistory')->with(58)->willReturn(false);
        self::assertFalse($this->priority_manager->deletePriority(ArtifactTestBuilder::anArtifact(58)->build()));
    }

    public function testPutArtifactAtAGivenRankProxiesToDao(): void
    {
        $this->priority_dao->expects($this->once())->method('putArtifactAtAGivenRank')->with(58, 1042);
        $this->priority_manager->putArtifactAtAGivenRank(ArtifactTestBuilder::anArtifact(58)->build(), 1042);
    }
}
