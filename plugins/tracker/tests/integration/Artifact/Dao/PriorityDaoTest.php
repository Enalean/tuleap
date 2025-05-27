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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Dao;

use ParagonIE\EasyDB\EasyDB;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class PriorityDaoTest extends TestIntegrationTestCase
{
    private PriorityDao $dao;
    private EasyDB $db;

    protected function setUp(): void
    {
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->dao = new PriorityDao();
    }

    public function testItStartsWithEmptyTable(): void
    {
        $this->assertOrder();
    }

    public function testItInsertOneElement(): void
    {
        $this->dao->putArtifactAtTheEndWithoutTransaction(1);
        $this->assertOrder(1);
    }

    public function testItInsertAnElementAtTheEnd(): void
    {
        $this->setInitialOrder(1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(42);
        $this->assertOrder(1, 42);
    }

    public function test42HasAnHigherPriorityThan1(): void
    {
        $this->setInitialOrder(1, 42);
        $this->dao->moveListOfArtifactsBefore([42], 1);
        $this->assertOrder(42, 1);
    }

    public function testItHasThreeMoreElementsAddedAtTheEnd(): void
    {
        $this->setInitialOrder(42, 1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(66);
        $this->dao->putArtifactAtTheEndWithoutTransaction(123);
        $this->dao->putArtifactAtTheEndWithoutTransaction(101);
        $this->assertOrder(42, 1, 66, 123, 101);
    }

    public function test1HasAGreaterPriorityThan101(): void
    {
        $this->setInitialOrder(42, 1, 66, 123, 101);
        $this->dao->moveListOfArtifactsBefore([1], 101);
        $this->assertOrder(42, 66, 123, 1, 101);
    }

    public function test42HasALowerPriorityThan1(): void
    {
        $this->setInitialOrder(42, 66, 123, 1, 101);
        $this->dao->moveArtifactAfter(42, 1);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test42HasAnHigherPriorityThan101(): void
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore([42], 101);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test1HasAnHigherPriorityThan123(): void
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveArtifactAfter(1, 123);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function testItDeletes123(): void
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->remove(123);
        $this->assertOrder(66, 1, 42, 101);
    }

    public function testItMovesManyArtifactsAtOnceBefore42(): void
    {
        $this->setInitialOrder(66, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore([42, 101], 1);
        $this->assertOrder(66, 42, 101, 1);
    }

    public function testItMovesManyArtifactsAtOnceAtTheBeginning(): void
    {
        $this->setInitialOrder(66, 42, 101, 1);
        $this->dao->moveListOfArtifactsBefore([1, 42], 66);
        $this->assertOrder(1, 42, 66, 101);
    }

    public function testItMovesManyArtifactsAtOnceAfter66(): void
    {
        $this->setInitialOrder(1, 42, 66, 101, 123);
        $this->dao->moveListOfArtifactsAfter([123, 42], 66);
        $this->assertOrder(1, 66, 123, 42, 101);
    }

    public function testItMovesManyArtifactsAtOnceAtTheSecondPosition(): void
    {
        $this->setInitialOrder(1, 66, 123, 42, 101);
        $this->dao->moveListOfArtifactsAfter([101, 42, 66], 1);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function testItMovesManyArtifactsAtOnceAtTheSamePosition(): void
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([66], 123);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function testItMovesManyArtifactsAtOnceAtTheVeryEnd(): void
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsAfter([1, 42, 66], 123);
        $this->assertOrder(101, 123, 1, 42, 66);
    }

    public function testItMovesManyArtifactsAtOnceAtTheBeforeLastPosition(): void
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([1, 42, 66], 123);
        $this->assertOrder(101, 1, 42, 66, 123);
    }

    public function testItMovesExtremitiesAtTheMiddle(): void
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([1, 123], 42);
        $this->assertOrder(101, 1, 123, 42, 66);
    }

    public function testItRaisesAnExceptionIfWeWantToMove1Before1(): void
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);

        $this->expectException(Tracker_Artifact_Exception_CannotRankWithMyself::class);

        $this->dao->moveListOfArtifactsBefore([1, 101], 1);
    }

    public function testItDoesntRelyOnMysqlInsertOrder(): void
    {
        $this->setInitialOrder(16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 28);

        $this->dao->moveListOfArtifactsAfter([28, 19, 17], 18);
        $this->assertOrder(16, 18, 28, 19, 17, 20, 21, 22, 23, 24, 25);
    }

    private function assertOrder(int ...$ids): void
    {
        $this->assertEquals(
            $ids,
            $this->getArtifactIdsOrderedByRank(),
        );
    }

    private function setInitialOrder(int ...$ids): void
    {
        foreach ($ids as $id) {
            $this->dao->putArtifactAtTheEndWithoutTransaction($id);
        }
    }

    private function getArtifactIdsOrderedByRank(): array
    {
        $ids     = [];
        $results = $this->db->run('SELECT artifact_id FROM tracker_artifact_priority_rank ORDER BY `rank`');
        foreach ($results as $row) {
            $ids[] = $row['artifact_id'];
        }
        return $ids;
    }
}
