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

/**
 * Routes request to the desired controller
 */
class TestingRouter {

    const DEFAULT_ACTION         = 'index';
    const RESOURCE_CAMPAIGN      = 'campaign';
    const RESOURCE_TESTEXECUTION = 'testexecution';
    const RESOURCE_TESTRESULT    = 'testresult';
    const RESOURCE_REQUIREMENT   = 'requirement';
    const RESOURCE_DEFECT        = 'defect';
    const RESOURCE_REPORT        = 'report';
    const RESOURCE_RELEASE       = 'release';
    const RESOURCE_TESTCASE      = 'testcase';

    public function route(Codendi_Request $request) {
        $controller = $this->getController($request);
        $action     = $this->getAction($request);
        call_user_func(array($controller, $action));
    }

    private function getController(Codendi_Request $request) {
        $project = $request->getProject();
        $conf = new TestingConfiguration($project);

        $stat_presenter_factory = new Testing_Campaign_CampaignStatPresenterFactory();
        $info_presenter_factory = new Testing_Campaign_CampaignInfoPresenterFactory($stat_presenter_factory);
        $campaign_dao     = new Testing_Campaign_CampaignDao();
        $campaign_factory = new Testing_Campaign_CampaignFactory($this->getTestExecutionCollectionFeeder());
        $campaign_manager = new Testing_Campaign_CampaignManager($campaign_dao, $campaign_factory);

        $release_info_presenter_collection_factory = new Testing_Release_ReleaseInfoPresenterCollectionFactory(
            $project,
            $conf->getReleaseTracker()
        );

        $testcase_association_dao = new Testing_Requirement_TestCaseAssociationDao();
        $release_association_dao  = new Testing_Requirement_ReleaseAssociationDao();
        $requirement_info_presenter_factory = new Testing_Requirement_RequirementInfoPresenterFactory(
            $project,
            $conf->getRequirementTracker(),
            $testcase_association_dao,
            $release_association_dao
        );

        $requirement_info_collection_presenter_factory = new Testing_Requirement_RequirementInfoCollectionPresenterFactory(
            $project,
            $conf->getRequirementTracker(),
            $requirement_info_presenter_factory,
            $release_info_presenter_collection_factory
        );

        $test_case_info_presenter_collection_factory = new Testing_TestCase_TestCaseInfoPresenterCollectionFactory(
            $project,
            $conf->getTestCaseTracker(),
            $testcase_association_dao
        );

        $matrix_row_presenter_collection_factory = new Testing_Campaign_MatrixRowPresenterCollectionFactory(
            $project,
            $this->getTestExecutionFactory(),
            new Testing_TestExecution_TestExecutionInfoPresenterFactory(),
            new Testing_TestExecution_TestExecutionDao(),
            $campaign_manager
        );

        $requested_resource = $request->getValidated('resource');
        switch ($requested_resource) {
            case self::RESOURCE_REQUIREMENT:
                return new Testing_Requirement_RequirementController($request, $testcase_association_dao, $release_association_dao, $requirement_info_collection_presenter_factory);
                break;
            case self::RESOURCE_TESTEXECUTION:
                $dao     = new Testing_TestExecution_TestExecutionDao();
                $manager = new Testing_TestExecution_TestExecutionManager($dao, $campaign_manager);
                return new Testing_TestExecution_TestExecutionController($request, $manager, $info_presenter_factory);
                break;
            case self::RESOURCE_TESTRESULT:
                $dao = new Testing_TestResult_TestResultDao();
                return new Testing_TestResult_TestResultController($request, $dao);
                break;
            case self::RESOURCE_DEFECT:
                $dao = new Testing_Defect_DefectDao();
                return new Testing_Defect_DefectController($request, $dao);
                break;
            case self::RESOURCE_REPORT:
                $defect_dao = new Testing_Defect_DefectDao();
                return new Testing_Report_ReportController($request, $defect_dao, $conf, $matrix_row_presenter_collection_factory);
                break;
            case self::RESOURCE_RELEASE:
                return new Testing_Release_ReleaseController($request, $release_association_dao, $conf);
                break;
            case self::RESOURCE_TESTCASE:
                return new Testing_TestCase_TestCaseController($request, $testcase_association_dao, $conf);
                break;
            case self::RESOURCE_CAMPAIGN:
            default:
                if ($requested_resource && $requested_resource != self::RESOURCE_CAMPAIGN) {
                    throw new Exception("Unknown resource '$requested_resource'");
                }

                $presenter_factory = new Testing_Campaign_CampaignPresenterFactory(
                    $stat_presenter_factory,
                    new Testing_TestExecution_TestExecutionInfoPresenterFactory()
                );
                $info_presenter_collection_factory = new Testing_Campaign_CampaignInfoPresenterCollectionFactory($campaign_manager, $info_presenter_factory);
                $creator = new Testing_Campaign_CampaignCreator($campaign_dao, new Testing_TestExecution_TestExecutionDao());
                $deletor = new Testing_Campaign_CampaignDeletor($campaign_dao, new Testing_TestExecution_TestExecutionDao());

                return new Testing_Campaign_CampaignController(
                    $request,
                    $info_presenter_collection_factory,
                    $creator,
                    $deletor,
                    $campaign_manager,
                    $info_presenter_factory,
                    $presenter_factory,
                    $stat_presenter_factory,
                    $release_info_presenter_collection_factory,
                    $test_case_info_presenter_collection_factory,
                    $requirement_info_collection_presenter_factory,
                    $matrix_row_presenter_collection_factory
                );
        }
    }

    private function getTestExecutionCollectionFeeder() {
        $dao     = new Testing_TestExecution_TestExecutionDao();
        $factory = $this->getTestExecutionFactory();

        return new Testing_TestExecution_TestExecutionCollectionFeeder($dao, $factory);
    }

    private function getTestExecutionFactory() {
        return new Testing_TestExecution_TestExecutionFactory(
            UserManager::instance(),
            $this->getTestResultCollectionFeeder(),
            $this->getTestExecutionDefectCollectionFeeder()
        );
    }

    private function getTestExecutionDefectCollectionFeeder() {
        $dao     = new Testing_Defect_DefectDao();
        $factory = new Testing_Defect_DefectFactory();

        return new Testing_Defect_DefectCollectionFeeder($dao, $factory);
    }

    private function getTestResultCollectionFeeder() {
        $dao = new Testing_TestResult_TestResultDao();
        $factory = new Testing_TestResult_TestResultFactory(UserManager::instance());

        return new Testing_TestResult_TestResultCollectionFeeder($dao, $factory);
    }

    private function getAction(Codendi_Request $request) {
        $action = $request->getValidated('action');
        if (! $action) {
            return self::DEFAULT_ACTION;
        }

        return $this->toCamelCase($action);
    }

    private function toCamelCase($string) {
        $str = str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
        return strtolower(substr($str, 0, 1)) . substr($str, 1);
    }
}
