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

class Testing_Campaign_CampaignCreator {

    /** @var Testing_Campaign_CampaignDao */
    private $dao;

    public function __construct(
        Testing_Campaign_CampaignDao $dao,
        Testing_TestExecution_TestExecutionDao $test_execution_dao
    ) {
        $this->dao                = $dao;
        $this->test_execution_dao = $test_execution_dao;
    }

    /**
     * @return bool truthy if success
     */
    public function create(Project $project, $name, $from_test_cases) {
        $campaign_id = $this->dao->create($project->getId(), $name);
        if (! $campaign_id) {
            return false;
        }

        foreach ($from_test_cases as $test_case_id) {
            $this->test_execution_dao->create($campaign_id, $test_case_id);
        }
        return true;
    }
}
