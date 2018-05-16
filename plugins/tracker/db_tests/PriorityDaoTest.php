<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../include/trackerPlugin.class.php';

class PriorityDaoTest extends TuleapDbTestCase {

    /** @var Tracker_Artifact_PriorityDao */
    private $dao;

    public function __construct() {
        parent::__construct();
    }

    public function setUp() {
        parent::setUp();
        $this->dao = new Tracker_Artifact_PriorityDao();
        $this->truncateTable('tracker_artifact_priority_rank');
    }

    public function tearDown() {
        $this->truncateTable('tracker_artifact_priority_rank');
        parent::tearDown();
    }

    public function itStartsWithEmptyTable() {
        $this->assertOrder();
    }

    public function itInsertOneElement() {
        $this->dao->putArtifactAtTheEndWithoutTransaction(1);
        $this->assertOrder(1);
    }

    public function itInsertAnElementAtTheEnd() {
        $this->setInitialOrder(1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(42);
        $this->assertOrder(1, 42);
    }

    public function test42HasAnHigherPriorityThan1() {
        $this->setInitialOrder(1, 42);
        $this->dao->moveListOfArtifactsBefore(array(42),1);
        $this->assertOrder(42, 1);
    }

    public function itHasThreeMoreElementsAddedAtTheEnd() {
        $this->setInitialOrder(42, 1);
        $this->dao->putArtifactAtTheEndWithoutTransaction(66);
        $this->dao->putArtifactAtTheEndWithoutTransaction(123);
        $this->dao->putArtifactAtTheEndWithoutTransaction(101);
        $this->assertOrder(42, 1, 66, 123, 101);
    }

    public function test1HasAGreaterPriorityThan101() {
        $this->setInitialOrder(42, 1, 66, 123, 101);
        $this->dao->moveListOfArtifactsBefore(array(1), 101);
        $this->assertOrder(42, 66, 123, 1, 101);
    }

    public function test42HasALowerPriorityThan1() {
        $this->setInitialOrder(42, 66, 123, 1, 101);
        $this->dao->moveArtifactAfter(42, 1);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test42HasAnHigherPriorityThan101() {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore(array(42), 101);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function test1HasAnHigherPriorityThan123() {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->moveArtifactAfter(1, 123);
        $this->assertOrder(66, 123, 1, 42, 101);
    }

    public function itDeletes123() {
        $this->setInitialOrder(66, 123, 1, 42, 101);
        $this->dao->remove(123);
        $this->assertOrder(66, 1, 42, 101);
    }

    public function itMovesManyArtifactsAtOnceBefore42() {
        $this->setInitialOrder(66, 1, 42, 101);
        $this->dao->moveListOfArtifactsBefore(array(42, 101), 1);
        $this->assertOrder(66, 42, 101, 1);
    }

    public function itMovesManyArtifactsAtOnceAtTheBeginning() {
        $this->setInitialOrder(66, 42, 101, 1);
        $this->dao->moveListOfArtifactsBefore(array(1, 42), 66);
        $this->assertOrder(1, 42, 66, 101);
    }

    public function itMovesManyArtifactsAtOnceAfter66() {
        $this->setInitialOrder(1, 42, 66, 101, 123);
        $this->dao->moveListOfArtifactsAfter(array(123, 42), 66);
        $this->assertOrder(1, 66, 123, 42, 101);
    }

    public function itMovesManyArtifactsAtOnceAtTheSecondPosition() {
        $this->setInitialOrder(1, 66, 123, 42, 101);
        $this->dao->moveListOfArtifactsAfter(array(101, 42, 66), 1);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function itMovesManyArtifactsAtOnceAtTheSamePosition() {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore(array(66), 123);
        $this->assertOrder(1, 101, 42, 66, 123);
    }

    public function itMovesManyArtifactsAtOnceAtTheVeryEnd() {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsAfter(array(1, 42, 66), 123);
        $this->assertOrder(101, 123, 1, 42, 66);
    }

      public function itMovesManyArtifactsAtOnceAtTheBeforeLastPosition() {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore(array(1, 42, 66), 123);
        $this->assertOrder(101, 1, 42, 66, 123);
    }

    public function itMovesExtremitiesAtTheMiddle() {
        $this->setInitialOrder(1, 101, 42, 66, 123);
        $this->dao->moveListOfArtifactsBefore(array(1, 123), 42);
        $this->assertOrder(101, 1, 123, 42, 66);
    }

    public function itRaisesAnExceptionIfWeWantToMove1Before1() {
        $this->setInitialOrder(1, 101, 42, 66, 123);

        $this->expectException();

        $this->dao->moveListOfArtifactsBefore(array(1, 101), 1);
    }

    public function itDoesntRelyOnMysqlInsertOrder() {
        $this->setInitialOrder(16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 28);

        $this->dao->moveListOfArtifactsAfter(array(28, 19, 17), 18);
        $this->assertOrder(16, 18, 28, 19, 17, 20, 21, 22, 23, 24, 25);
    }

    private function assertOrder() {
        $expected_order = func_get_args();
        $this->assertEqual(
            $this->getArtifactIdsOrderedByRank(),
            $expected_order
        );
    }

    private function setInitialOrder() {
        foreach (func_get_args() as $id) {
            $this->dao->putArtifactAtTheEndWithoutTransaction($id);
        }
    }

    private function getArtifactIdsOrderedByRank()
    {
        $ids = array();
        $dar = $this->dao->retrieve(
            "SELECT artifact_id
            FROM tracker_artifact_priority_rank
            ORDER BY rank"
        );
        foreach ($dar as $row) {
            $ids[] = $row['artifact_id'];
        }
        return $ids;
    }
}
