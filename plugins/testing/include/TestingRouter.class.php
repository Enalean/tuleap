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
    const RESOURCE_REQUIREMENT   = 'requirement';

    public function route(Codendi_Request $request) {
        $controller = $this->getController($request);
        $action     = $this->getAction($request);
        call_user_func(array($controller, $action));
    }

    private function getController(Codendi_Request $request) {
        $stat_presenter_factory = new Testing_Campaign_CampaignStatPresenterFactory();
        $info_presenter_factory = new Testing_Campaign_CampaignInfoPresenterFactory($stat_presenter_factory);
        $campaign_dao     = new Testing_Campaign_CampaignDao();
        $campaign_factory = new Testing_Campaign_CampaignFactory($this->getTestExecutionCollectionFeeder());
        $campaign_manager = new Testing_Campaign_CampaignManager($campaign_dao, $campaign_factory);
        switch ($request->getValidated('resource')) {
            case self::RESOURCE_REQUIREMENT:
                return new Testing_Requirement_RequirementController($request);
                break;
            case self::RESOURCE_TESTEXECUTION:
                $dao     = new Testing_TestExecution_TestExecutionDao();
                $manager = new Testing_TestExecution_TestExecutionManager($dao, $campaign_manager);
                return new Testing_TestExecution_TestExecutionController($request, $manager, $info_presenter_factory);
                break;
            case self::RESOURCE_CAMPAIGN:
            default:
                $presenter_factory = new Testing_Campaign_CampaignPresenterFactory(
                    $stat_presenter_factory,
                    new Testing_TestExecution_TestExecutionInfoPresenterFactory()
                );
                $info_presenter_collection_factory = new Testing_Campaign_CampaignInfoPresenterCollectionFactory($campaign_manager, $info_presenter_factory);
                $creator = new Testing_Campaign_CampaignCreator($campaign_dao);
                return new Testing_Campaign_CampaignController($request, $info_presenter_collection_factory, $creator, $campaign_manager, $info_presenter_factory, $presenter_factory);
        }
    }

    private function getTestExecutionCollectionFeeder() {
        $dao     = new Testing_TestExecution_TestExecutionDao();
        $factory = new Testing_TestExecution_TestExecutionFactory(UserManager::instance());

        return new Testing_TestExecution_TestExecutionCollectionFeeder($dao, $factory);
    }

    private function getAction(Codendi_Request $request) {
        $action = $request->getValidated('action');
        if (! $action) {
            return self::DEFAULT_ACTION;
        }

        return $action;
    }
}
