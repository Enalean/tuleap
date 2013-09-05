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
        switch ($request->getValidated('resource')) {
            case self::RESOURCE_REQUIREMENT:
                return new Testing_Requirement_RequirementController($request);
                break;
            case self::RESOURCE_TESTEXECUTION:
                return new Testing_TestExecution_TestExecutionController($request);
                break;
            case self::RESOURCE_CAMPAIGN:
            default:
                $dao     = new Testing_Campaign_CampaignDao();
                $factory = new Testing_Campaign_CampaignFactory();
                $manager = new Testing_Campaign_CampaignManager($dao, $factory);
                $presenter_factory = new Testing_Campaign_CampaignPresenterFactory($presenter_factory);
                $presenter_collection_factory = new Testing_Campaign_CampaignPresenterCollectionFactory($manager, $presenter_factory);
                $creator = new Testing_Campaign_CampaignCreator($dao);
                return new Testing_Campaign_CampaignController($request, $presenter_collection_factory, $creator, $manager, $presenter_factory);
        }
    }

    private function getAction(Codendi_Request $request) {
        $action = $request->getValidated('action');
        if (! $action) {
            return self::DEFAULT_ACTION;
        }

        return $action;
    }
}
