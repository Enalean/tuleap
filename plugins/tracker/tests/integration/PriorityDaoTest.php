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

namespace Tuleap\Tracker;

use Tracker_Artifact_Exception_CannotRankWithMyself;
use Tracker_Artifact_PriorityDao;
use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class PriorityDaoTest extends TestIntegrationTestCase
{
    private Tracker_Artifact_PriorityDao $dao;
    private \ParagonIE\EasyDB\EasyDB $db;

    protected function setUp(): void
    {
        $this->db  = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->dao = new Tracker_Artifact_PriorityDao();
    }

    public function testItStartsWithEmptyTable()
    {
        $this->assertOrder();
    }

    public function testItInsertOneElement()
    {
        $this->dao->putArtifactAtTheEndWithoutTransaction(1);
        $this->assertOrder(1);
    }

    public function testItInsertAnElementAtTheEnd()
    {
        $this->setInitialOrder(1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(42);
        $this->assertOrder(1, 42);
    }

    public function test42HasAnHigherPriorityThan1()
    {
        $this->setInitialOrder(1, 42);
        $this->dao->moveListOfArtifactsBefore([42], 1);
        $this->assertOrder(42, 1);
    }

    public function testItHasThreeMoreElementsAddedAtTheEnd()
    {
        $this->setInitialOrder(42, 1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(66);
        $this->dao->putArtifactAtTheEndWithoutTransaction(123);
        $this->dao->putArtifactAtTheEndWithoutTransaction(101);
        $this->assertOrder(42, 1, 66, 123, 101);
    }

    public function test1HasAGreaterPriorityThan101()
    {
        $this->setInitialOrder(42, 1, 66, 123, 101);
        $this->dao->moveListOfArtifactsBefore([1], 101);
        $this->assertOrder(42, 66, 123, 1, 101);
    }

    public function test42HasALowerPriorityThan1()
    {
        $this->setInitialOrder(42, 66, 123, 1, 101);
        $this->dao->moveArtifactAfter(42, 1);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test42HasAnHigherPriorityThan101()
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore([42], 101);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test1HasAnHigherPriorityThan123()
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveArtifactAfter(1, 123);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function testItDeletes123()
    {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->remove(123);
        $this->assertOrder(66, 1, 42, 101);
    }

    public function testItMovesManyArtifactsAtOnceBefore42()
    {
        $this->setInitialOrder(66, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore([42, 101], 1);
        $this->assertOrder(66, 42, 101, 1);
    }

    public function testItMovesManyArtifactsAtOnceAtTheBeginning()
    {
        $this->setInitialOrder(66, 42, 101, 1);
        $this->dao->moveListOfArtifactsBefore([1, 42], 66);
        $this->assertOrder(1, 42, 66, 101);
    }

    public function testItMovesManyArtifactsAtOnceAfter66()
    {
        $this->setInitialOrder(1, 42, 66, 101, 123);
        $this->dao->moveListOfArtifactsAfter([123, 42], 66);
        $this->assertOrder(1, 66, 123, 42, 101);
    }

    public function testItMovesManyArtifactsAtOnceAtTheSecondPosition()
    {
        $this->setInitialOrder(1, 66, 123, 42, 101);
        $this->dao->moveListOfArtifactsAfter([101, 42, 66], 1);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function testItMovesManyArtifactsAtOnceAtTheSamePosition()
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([66], 123);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function testItMovesManyArtifactsAtOnceAtTheVeryEnd()
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsAfter([1, 42, 66], 123);
        $this->assertOrder(101, 123, 1, 42, 66);
    }

    public function testItMovesManyArtifactsAtOnceAtTheBeforeLastPosition()
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([1, 42, 66], 123);
        $this->assertOrder(101, 1, 42, 66, 123);
    }

    public function testItMovesExtremitiesAtTheMiddle()
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore([1, 123], 42);
        $this->assertOrder(101, 1, 123, 42, 66);
    }

    public function testItRaisesAnExceptionIfWeWantToMove1Before1()
    {
        $this->setInitialOrder(1, 101, 42, 66, 123);

        $this->expectException(Tracker_Artifact_Exception_CannotRankWithMyself::class);

        $this->dao->moveListOfArtifactsBefore([1, 101], 1);
    }

    public function testItDoesntRelyOnMysqlInsertOrder()
    {
        $this->setInitialOrder(16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 28);

        $this->dao->moveListOfArtifactsAfter([28, 19, 17], 18);
        $this->assertOrder(16, 18, 28, 19, 17, 20, 21, 22, 23, 24, 25);
    }

    private function assertOrder()
    {
        $expected_order = func_get_args();
        $this->assertEquals(
            $expected_order,
            $this->getArtifactIdsOrderedByRank(),
        );
    }

    private function setInitialOrder()
    {
        foreach (func_get_args() as $id) {
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
