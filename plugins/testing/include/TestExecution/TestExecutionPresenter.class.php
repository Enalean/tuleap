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

    public function __construct(
        Testing_TestExecution_TestExecution $test_execution,
        Testing_Campaign_CampaignInfoPresenter $campaign,
        array $results /** @var Testing_TestResult_TestResultPresenter[] */,
        $specification,
        Testing_Defect_DefectPresenterCollection $defects,
        TestingFacadeTrackerCreationPresenter $create_defect_form
    ) {
        $this->campaign = $campaign;
        $this->name     = $test_execution->getName();
        $this->assignee = $test_execution->getAssignee()->getRealName();
        $this->results  = $results;
        $project_id     = $test_execution->getCampaign()->getProjectId();
        $this->create_result_uri = '/plugins/testing/?group_id='. $project_id .'&resource=testresult&action=create&execution_id='. $test_execution->getId();
        $this->create_defect_uri = '/plugins/testing/?group_id='. $project_id .'&resource=defect&action=create&execution_id='. $test_execution->getId();

        $this->last_result   = end($results);
        $this->has_results   = (bool)$this->last_result->executed_on;
        $this->specification = $specification;
        $this->defects       = $defects;
        $this->has_defects   = count($this->defects);

        $this->create_defect_form = $create_defect_form;
    }
}
