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
        $this->markThisTestUnderDevelopment();
    }

    public function tearDown() {
        parent::tearDown();
        $this->truncateTable('tracker_artifact_priority');
    }

    public function itInsertArtifactsWithPriority() {
        $this->dao = new Tracker_Artifact_PriorityDao();

        $this->truncateTable('tracker_artifact_priority');

        $this->assertEqual($this->dump_priorities("Table is empty"),
"Table is empty
.
");

        $this->dao->update("INSERT INTO tracker_artifact_priority VALUES (NULL, 1, 0), (1, NULL, 1)");
        $this->assertEqual($this->dump_priorities("Table contains one element"),
"Table contains one element
,1,0,
1,,1,
.
");
        $this->dao->putArtifactAtTheEnd(42);
        $this->assertEqual($this->dump_priorities("An element is inserted at the end"),
"An element is inserted at the end
,1,0,
1,42,1,
42,,2,
.
");
        $this->dao->moveArtifactBefore(42,1);
        $this->assertEqual($this->dump_priorities("42 is > than 1"),
"42 is > than 1
,42,0,
42,1,1,
1,,2,
.
");
        $this->dao->putArtifactAtTheEnd(66);
        $this->dao->putArtifactAtTheEnd(123);
        $this->dao->putArtifactAtTheEnd(101);
        $this->assertEqual($this->dump_priorities("Three more elements are added at the end"),
"Three more elements are added at the end
,42,0,
42,1,1,
1,66,2,
66,123,3,
123,101,4,
101,,5,
.
");
        $this->dao->moveArtifactBefore(1,101);
        $this->assertEqual($this->dump_priorities("1 is > than 101"),
"1 is > than 101
,42,0,
42,66,1,
66,123,2,
123,1,3,
1,101,4,
101,,5,
.
");
        $this->dao->moveArtifactAfter(42, 1);
        $this->assertEqual($this->dump_priorities("42 is < than 1"),
"42 is < than 1
,66,0,
66,123,1,
123,1,2,
1,42,3,
42,101,4,
101,,5,
.
");
        $this->dao->moveArtifactBefore(42,101);
        $this->assertEqual($this->dump_priorities("42 is > than 101 (nothing changed)"),
"42 is > than 101 (nothing changed)
,66,0,
66,123,1,
123,1,2,
1,42,3,
42,101,4,
101,,5,
.
");
        $this->dao->moveArtifactAfter(1,123);
        $this->assertEqual($this->dump_priorities("1 is < than 123 (nothing changed)"),
"1 is < than 123 (nothing changed)
,66,0,
66,123,1,
123,1,2,
1,42,3,
42,101,4,
101,,5,
.
");
        $this->dao->remove(123);
        $this->assertEqual($this->dump_priorities("123 was deleted"),
"123 was deleted
,66,0,
66,1,1,
1,42,2,
42,101,3,
101,,4,
.
");
    }

    function dump_priorities($msg) {
        $msg = $msg.PHP_EOL;
        $dar = $this->dao->retrieve("SELECT * FROM tracker_artifact_priority ORDER BY rank");
        foreach ($dar as $row) {
            foreach ($row as $cell) {
                $msg .= $cell .',';
            }
            $msg .= PHP_EOL;
        }
        $msg .= '.'. PHP_EOL;
        return $msg;
    }
}

?>
