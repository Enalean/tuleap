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

class Testing_Campaign_CampaignManager {

    /** @var Testing_Campaign_CampaignDao */
    private $dao;

    /** @var Testing_Campaign_CampaignFactory */
    private $factory;

    public function __construct(Testing_Campaign_CampaignDao $dao, Testing_Campaign_CampaignFactory $factory) {
        $this->dao     = $dao;
        $this->factory = $factory;
    }

    /**
     * @return Testing_Campaign_Campaign
     */
    public function getListOfCampaignsForProject(Project $project) {
        $list_of_campaigns = array();
        foreach ($this->dao->searchByProjectId($project->getId()) as $row) {
            $campaign = $this->factory->getInstanceFromRow($project, $row);
            $list_of_campaigns[] = $campaign;
        }
        return $list_of_campaigns;
    }
}
