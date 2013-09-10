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

class Testing_Requirement_TestCaseAssociationDao extends DataAccessObject {

    public function searchByRequirementId($id) {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_testing_requirement_testversion WHERE requirement_id = $id";

        return $this->retrieve($sql);
    }

    public function searchForAvailablesByRequirementId($tracker_id, $requirement_id) {
        $tracker_id     = $this->da->escapeInt($tracker_id);
        $requirement_id = $this->da->escapeInt($requirement_id);

        $sql = "SELECT id AS testversion_id
                FROM tracker_artifact
                    LEFT JOIN
                    ( SELECT testversion_id FROM plugin_testing_requirement_testversion WHERE requirement_id = $requirement_id ) as R
                    ON (testversion_id = id)
                WHERE tracker_artifact.tracker_id = $tracker_id
                    AND R.testversion_id IS NULL";

        return $this->retrieve($sql);
    }

    public function create($requirement_id, $testversion_id) {
        $requirement_id = $this->da->escapeInt($requirement_id);
        $testversion_id = $this->da->escapeInt($testversion_id);

        $sql = "REPLACE INTO plugin_testing_requirement_testversion(requirement_id, testversion_id)
                VALUES ($requirement_id, $testversion_id)";

        return $this->update($sql);
    }

    public function delete($requirement_id, $testversion_id) {
        $requirement_id = $this->da->escapeInt($requirement_id);
        $testversion_id = $this->da->escapeInt($testversion_id);

        $sql = "DELETE FROM plugin_testing_requirement_testversion
                WHERE requirement_id = $requirement_id
                  AND testversion_id = $testversion_id";

        return $this->update($sql);
    }

    public function searchForSum($tracker_id) {
        $tracker_id     = $this->da->escapeInt($tracker_id);

        $sql = "SELECT requirement_id, COUNT(testversion_id) as nb
                FROM plugin_testing_requirement_testversion
                    INNER JOIN tracker_artifact ON (requirement_id = id)
                WHERE tracker_artifact.tracker_id = $tracker_id
                GROUP BY requirement_id";

        return $this->retrieve($sql);
    }

    public function searchForRequirementsSum($tracker_id) {
        $tracker_id     = $this->da->escapeInt($tracker_id);

        $sql = "SELECT testversion_id, COUNT(requirement_id) as nb
                FROM plugin_testing_requirement_testversion
                    INNER JOIN tracker_artifact ON (testversion_id = id)
                WHERE tracker_artifact.tracker_id = $tracker_id
                GROUP BY testversion_id";

        return $this->retrieve($sql);
    }
}
