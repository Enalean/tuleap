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

class Testing_Campaign_MatrixRowPresenterCollectionFactory {

    public function __construct(
        Project $project,
        Testing_TestExecution_TestExecutionFactory $exec_factory,
        Testing_TestExecution_TestExecutionInfoPresenterFactory $exec_presenter_factory,
        Testing_TestExecution_TestExecutionDao $dao
    ) {
        $this->project                = $project;
        $this->exec_factory           = $exec_factory;
        $this->exec_presenter_factory = $exec_presenter_factory;
        $this->dao                    = $dao;
    }

    public function getCollection(Testing_Campaign_Campaign $campaign) {
        $collection = new Testing_Campaign_MatrixRowPresenterCollection();

        $testexecution_dar = $this->dao->searchByCampaignIdGroupByRequirement($campaign->getId());

        $nb_of_tests = array();
        foreach ($testexecution_dar as $row) {
            $requirement_id = $row['requirement_id'];
            $execution_id   = $row['id'];
            if (! isset($nb_of_tests[$requirement_id])) {
                $nb_of_tests[$requirement_id] = 0;
            }
            $nb_of_tests[$requirement_id]++;
        }

        $last_requirement_id = null;
        foreach ($testexecution_dar as $row) {
            $requirement_id = $row['requirement_id'];
            $execution_id   = $row['id'];

            if ($last_requirement_id !== $requirement_id) {
                $requirement = new Testing_Requirement_Requirement($requirement_id);
                $requirement_result_presenter = new Testing_Requirement_RequirementResultPresenter(
                    $this->project,
                    $requirement,
                    $nb_of_tests[$requirement_id] + 1 // +1 for the supplementary rowspan
                );
            }

            $execution = $this->exec_factory->getInstanceFromRow($campaign, $row);
            $exec_info_presenter = $this->exec_presenter_factory->getPresenter($execution);

            if ($last_requirement_id !== $requirement_id) {
                $requirement_row_presenter = new Testing_Campaign_MatrixRowRequirementPresenter($requirement_result_presenter);
                $collection->append($requirement_row_presenter);
            }
            $presenter = new Testing_Campaign_MatrixRowTestExecutionPresenter($exec_info_presenter);
            $collection->append($presenter);

            switch ($execution->getLastTestResult()->getStatus()) {
            case Testing_TestResult_TestResult::NOT_RUN:
                $requirement_result_presenter->has_one_not_run = 1;
                break;
            case Testing_TestResult_TestResult::PASS:
                $requirement_result_presenter->has_one_passed = 1;
                break;
            case Testing_TestResult_TestResult::FAIL:
                $requirement_result_presenter->has_one_failed = 1;
                var_dump(
                    $requirement_result_presenter->is_failed(),
                    $requirement_result_presenter->is_not_completed(),
                    $requirement_result_presenter->is_not_run(),
                    $requirement_result_presenter->is_passed()
                );
                break;
            case Testing_TestResult_TestResult::NOT_COMPLETED:
                $requirement_result_presenter->has_one_not_completed = 1;
                break;
            }
            $last_requirement_id = $requirement_id;
        }
        return $collection;
    }
}
