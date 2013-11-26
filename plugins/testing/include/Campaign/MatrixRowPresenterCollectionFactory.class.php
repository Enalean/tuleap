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
        Testing_TestExecution_TestExecutionDao $dao,
        Testing_Campaign_CampaignManager $campaign_manager
    ) {
        $this->project                = $project;
        $this->exec_factory           = $exec_factory;
        $this->exec_presenter_factory = $exec_presenter_factory;
        $this->dao                    = $dao;
        $this->campaign_manager       = $campaign_manager;
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
                break;
            case Testing_TestResult_TestResult::NOT_COMPLETED:
                $requirement_result_presenter->has_one_not_completed = 1;
                break;
            }
            $last_requirement_id = $requirement_id;
        }
        return $collection;
    }

    public function getReleasePresenter($testcase_tracker_id, $cycle_tracker) {
        $testexecution_dar = $this->dao->searchAll($testcase_tracker_id->getId(), $cycle_tracker->getId());

        $nb_of_tests_by_requirements = array();
        $nb_of_tests_by_releases     = array();
        $requirements_by_releases    = array();

        $release_nodes_by_id     = array();
        $cycle_nodes_by_id       = array();
        $requirement_nodes_by_id = array();
        $execution_nodes_by_id   = array();
        $artifact_factory        = Tracker_ArtifactFactory::instance();
        $current_user            = UserManager::instance()->getCurrentUser();
        $root                    = new TreeNode();
        foreach ($testexecution_dar as $row) {
            $cycle_id       = $row['release_id'];
            $requirement_id = $row['requirement_id'];
            $execution_id   = $row['id'];

            $cycle       = $artifact_factory->getArtifactById($cycle_id);
            $release     = $cycle->getParent($current_user);
            $requirement = $artifact_factory->getArtifactById($requirement_id);
            $campaign    = $this->campaign_manager->getCampaign($this->project, $row['campaign_id']);
            $execution   = $this->exec_factory->getInstanceFromRow($campaign, $row);
            $last_result = $execution->getLastTestResult();

            if (! isset($release_nodes_by_id[$release->getId()])) {
                $release_nodes_by_id[$release->getId()] = new TreeNode(array('label' => $release->getTitle()));
                $root->addChild($release_nodes_by_id[$release->getId()]);
            }

            if (! isset($cycle_nodes_by_id[$cycle->getId()])) {
                $cycle_nodes_by_id[$cycle->getId()] = new TreeNode(array('label' => $cycle->getTitle()));
                $release_nodes_by_id[$release->getId()]->addChild($cycle_nodes_by_id[$cycle->getId()]);
            }

            if (! isset($requirement_nodes_by_id[$cycle->getId()][$requirement->getId()])) {
                $requirement_nodes_by_id[$cycle->getId()][$requirement->getId()] = new TreeNode(array('label' => $requirement->getTitle()));
                $cycle_nodes_by_id[$cycle->getId()]->addChild($requirement_nodes_by_id[$cycle->getId()][$requirement->getId()]);
            }
            $execution_nodes_by_id[$execution_id] = new TreeNode(array(
                'label'            => $execution->getName(),
                'is_passed'        => $last_result->getStatus() == Testing_TestResult_TestResult::PASS,
                'is_failed'        => $last_result->getStatus() == Testing_TestResult_TestResult::FAIL,
                'is_not_run'       => $last_result->getStatus() == Testing_TestResult_TestResult::NOT_RUN,
                'is_not_completed' => $last_result->getStatus() == Testing_TestResult_TestResult::NOT_COMPLETED,
                'nb_of_results_to_display' => 1
            ));
            $requirement_nodes_by_id[$cycle->getId()][$requirement->getId()]->addChild($execution_nodes_by_id[$execution_id]);

            if (! isset($nb_of_tests_by_requirements[$cycle_id][$requirement_id])) {
                $nb_of_tests_by_requirements[$cycle_id][$requirement_id] = 0;
            }
            if (! isset($nb_of_tests_by_releases[$cycle_id])) {
                $nb_of_tests_by_releases[$cycle_id] = 0;
            }
            $nb_of_tests_by_requirements[$cycle_id][$requirement_id]++;
            $nb_of_tests_by_releases[$cycle_id]++;
            $requirements_by_releases[$cycle_id][$requirement_id] = 1;
        }
        $this->fillUp($root);
        $stack = array();
        $this->convertToArray($root, $stack);

        return $stack;
    }

    public function fillUp(TreeNode $root, $level = 0) {
        $data = $root->getData();
        if (isset($data['is_failed'])) {
            return $data;
        }

        $nb_of_results_to_display = 1;
        $has_one_not_run = $has_one_passed = $has_one_failed = $has_one_not_completed = 0;
        foreach ($root->getChildren() as $child) {
            $child_data = $this->fillUp($child, $level + 1);
            if ($child_data['is_not_run']) {
                $has_one_not_run = 1;
            }
            if ($child_data['is_passed']) {
                $has_one_passed = 1;
            }
            if ($child_data['is_failed']) {
                $has_one_failed = 1;
            }
            if ($child_data['is_not_completed']) {
                $has_one_not_completed = 1;
            }
            $nb_of_results_to_display += $child_data['nb_of_results_to_display'];
        }
        $data['is_failed']        = $has_one_failed > 0;
        $data['is_not_completed'] = ! $data['is_failed'] && $has_one_not_completed > 0;
        $data['is_not_run']       = ! $data['is_failed'] && ! $data['is_not_completed'] && $has_one_not_run > 0;
        $data['is_passed']        = ! $data['is_not_run'] && ! $data['is_failed'] && ! $data['is_not_completed'] && $has_one_passed > 0;
        $data['nb_of_results_to_display'] = $nb_of_results_to_display;
        $root->setData($data);

        return $data;
    }

    public function convertToArray(TreeNode $root, array &$stack) {
        $data = $root->getData();
        if (isset($data['label'])) {
            $stack[] = $data;
        }
        foreach ($root->getChildren() as $child) {
            $this->convertToArray($child, $stack);
        }
    }
}
