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

class MigrateTrackerTest extends TuleapDbTestCase {

    public function itCreatesTheTracker() {
        $res = db_query('SELECT * FROM artifact_group_list WHERE item_name = "bug"');
        $row = db_fetch_array($res);

        $this->assertEqual($row['item_name'], 'bug');
        $trackerv3_id = $row['group_artifact_id'];
    }

    public function itCreatesAllFieldsOfv3Intov5() {
        $res = db_query('SELECT * FROM artifact_group_list WHERE item_name = "bug"');
        $row = db_fetch_array($res);
        $trackerv3_id = $row['group_artifact_id'];

        $res = db_query("SELECT * FROM artifact_field WHERE group_artifact_id = $trackerv3_id");
        $this->assertTrue(db_numrows($res) > 0);
    }
}

?>