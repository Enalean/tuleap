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

class Testing_TestExecution_TestExecutionDao extends DataAccessObject {

    public function searchByCampaignId($campaign_id) {
        $campaign_id = $this->da->escapeInt($campaign_id);

        $sql = "SELECT * FROM plugin_testing_testexecution WHERE campaign_id = $campaign_id";

        return $this->retrieve($sql);
    }

    public function searchById($id) {
        $id = $this->da->escapeInt($id);

        $sql = "SELECT * FROM plugin_testing_testexecution WHERE id = $id";

        return $this->retrieve($sql);
    }

    public function create($campaign_id, $test_version_id) {
        $campaign_id     = $this->da->escapeInt($campaign_id);
        $test_version_id = $this->da->escapeInt($test_version_id);

        $sql = "INSERT INTO plugin_testing_testexecution(campaign_id, test_case_id, test_version_id, assigned_to)
                VALUES ($campaign_id, $test_version_id, $test_version_id, 101)";

        return $this->update($sql);
    }

    public function deleteByCampaignId($campaign_id) {
        $campaign_id     = $this->da->escapeInt($campaign_id);

        $sql = "DELETE FROM plugin_testing_testexecution WHERE campaign_id = $campaign_id";

        return $this->update($sql);
    }

    public function searchByCampaignIdGroupByRequirement($campaign_id) {
        $campaign_id = $this->da->escapeInt($campaign_id);

        $sql = "SELECT requirement.id as requirement_id, execution.*
                FROM plugin_testing_campaign AS campaign
                    INNER JOIN plugin_testing_testexecution AS execution ON (campaign.id = execution.campaign_id)
                    INNER JOIN tracker_artifact AS testcase ON (testcase.id = execution.test_version_id)
                    INNER JOIN plugin_testing_requirement_testversion ON (testversion_id = testcase.id)
                    INNER JOIN tracker_artifact AS requirement ON (requirement_id = requirement.id)
                WHERE campaign.id = $campaign_id
                ORDER BY requirement_id, execution.id";

        return $this->retrieve($sql);
    }
}
