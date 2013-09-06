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

class Testing_TestExecution_TestExecutionPresenter {

    public function __construct(Testing_TestExecution_TestExecution $test_execution, Testing_Campaign_CampaignInfoPresenter $campaign) {
        $this->campaign = $campaign;
        $this->name     = $test_execution->getId();
        $this->assignee = $test_execution->getAssignee()->getRealName();
        $project_id     = $test_execution->getCampaign()->getProjectId();
        $this->create_uri = '/plugins/testing/?group_id='. $project_id .'&resource=testexecution&action=create';

        $last_result = $test_execution->getLastTestResult();
        $this->is_passed  = $last_result->getStatus() == Testing_TestResult_TestResult::PASS;
        $this->is_failed  = $last_result->getStatus() == Testing_TestResult_TestResult::FAIL;
        $this->is_not_run = $last_result->getStatus() == Testing_TestResult_TestResult::NOT_RUN;
    }
}
