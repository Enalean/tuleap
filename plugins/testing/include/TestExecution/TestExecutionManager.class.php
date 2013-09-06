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

class Testing_TestExecution_TestExecutionManager {

    /** @var Testing_TestExecution_TestExecutionDao */
    private $dao;

    /** @var Testing_Campaign_CampaignManager */
    private $campaign_manager;

    public function __construct(
        Testing_TestExecution_TestExecutionDao $dao,
        Testing_Campaign_CampaignManager $campaign_manager
    ) {
        $this->dao              = $dao;
        $this->campaign_manager = $campaign_manager;
    }

    public function getTestExecution(Project $project, $id) {
        $row = $this->dao->searchById($id)->getRow();
        $campaign = $this->campaign_manager->getCampaign($project, $row['campaign_id']);
        foreach ($campaign->getListOfTestExecutions() as $execution) {
            if ($execution->getId() == $id) {
                return $execution;
            }
        }
    }
}
