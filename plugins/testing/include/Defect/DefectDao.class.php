<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once 'common/dao/include/DataAccessObject.class.php';

class Testing_Defect_DefectDao extends DataAccessObject {

    public function searchByExecutionId($id) {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_testing_testexecution_defect WHERE testexecution_id = $id";

        return $this->retrieve($sql);
    }

    public function create($testexecution_id, $defect_id) {
        $testexecution_id = $this->da->escapeInt($testexecution_id);
        $defect_id        = $this->da->escapeInt($defect_id);

        $sql = "REPLACE INTO plugin_testing_testexecution_defect(testexecution_id, defect_id)
                VALUES ($testexecution_id, $defect_id)";

        return $this->update($sql);
    }

    public function searchDefectsAndReleases($defect_tracker_id, $release_tracker_id) {
        $defect_tracker_id  = $this->da->escapeInt($defect_tracker_id);
        $release_tracker_id = $this->da->escapeInt($release_tracker_id);

        $sql = "SELECT product_version.id AS release_id, defect.id AS defect_id
                FROM
                    tracker_artifact AS defect
                    INNER JOIN plugin_testing_testexecution_defect ON (defect_id = defect.id)
                    INNER JOIN plugin_testing_testexecution AS execution ON (testexecution_id = execution.id)
                    INNER JOIN plugin_testing_campaign AS campaign ON (campaign_id = campaign.id)
                    INNER JOIN tracker_artifact AS product_version ON (product_version.id = product_version_id)
                WHERE defect.tracker_id = $defect_tracker_id
                  AND product_version.tracker_id = $release_tracker_id
                ORDER BY product_version.id";

        return $this->retrieve($sql);
    }
}
