<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/Tracker/Artifact/dao/PriorityDao.class.php';

class PriorityDaoTest extends TuleapDbTestCase {

    public function __construct() {
        parent::__construct();
    }

    public function __destruct() {
        $this->truncateTable('tracker_artifact_priority');
        $this->mysqli->query('INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank) VALUES (NULL, NULL, 0)');
    }

    public function setUp() {
        parent::setUp();
        $this->dao = new Tracker_Artifact_PriorityDao();
    }


    public function itStartsWithEmptyTable() {
        $this->assertEqual($this->getPriorities(), array(
            array(null, null, 0)
        ));
    }

    public function itInsertOneElement() {
        $this->dao->putArtifactAtTheEnd(1);
        $this->assertEqual($this->getPriorities(), array(
            array(null, 1,    0),
            array(1,    null, 1),
        ));
    }

    public function itInsertAnElementAtTheEnd() {
        $this->dao->putArtifactAtTheEnd(42);
        $this->assertEqual($this->getPriorities(), array(
            array(null, 1,    0),
            array(1,    42,   1),
            array(42,   null, 2),
        ));
    }

    public function test42HasAnHigherPriorityThan1() {
        $this->dao->moveArtifactBefore(42,1);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 42,   0),
             array(42,   1,    1),
             array(1,    null, 2),
        ));
    }

    public function itHasThreeMoreElementsAddedAtTheEnd() {
        $this->dao->putArtifactAtTheEnd(66);
        $this->dao->putArtifactAtTheEnd(123);
        $this->dao->putArtifactAtTheEnd(101);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 42,   0),
             array(42,   1,    1),
             array(1,    66,   2),
             array(66,   123,  3),
             array(123,  101,  4),
             array(101,  null, 5),
        ));
    }

    public function test1HasAGreaterPriorityThan101() {
        $this->dao->moveArtifactBefore(1,101);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 42,   0),
             array(42,   66,   1),
             array(66,   123,  2),
             array(123,  1,    3),
             array(1,    101,  4),
             array(101,  null, 5),
        ));
    }

    public function test42HasALowerPriorityThan1() {
        $this->dao->moveArtifactAfter(42, 1);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 66,   0),
             array(66,   123,  1),
             array(123,  1,    2),
             array(1,    42,   3),
             array(42,   101,  4),
             array(101,  null, 5),
        ));
    }

    public function test42HasAnHigherPriorityThan101() {
        $this->dao->moveArtifactBefore(42,101);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 66,   0),
             array(66,   123,  1),
             array(123,  1,    2),
             array(1,    42,   3),
             array(42,   101,  4),
             array(101,  null, 5),
        ));
    }

    public function test1HasAnHigherPriorityThan123() {
        $this->dao->moveArtifactAfter(1,123);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 66,   0),
             array(66,   123,  1),
             array(123,  1,    2),
             array(1,    42,   3),
             array(42,   101,  4),
             array(101,  null, 5),
        ));
    }

    public function itDeletes123() {
        $this->dao->remove(123);
        $this->assertEqual($this->getPriorities(), array(
             array(null, 66,   0),
             array(66,   1,    1),
             array(1,    42,   2),
             array(42,   101,  3),
             array(101,  null, 4),
        ));
    }

    private function getPriorities() {
        $msg = array();
        $dar = $this->dao->retrieve("SELECT * FROM tracker_artifact_priority ORDER BY rank");
        foreach ($dar as $row) {
            $r = array();
            foreach ($row as $cell) {
                $r[] = $cell;
            }
            $msg[] = $r;
        }
        return $msg;
    }
}

?>
