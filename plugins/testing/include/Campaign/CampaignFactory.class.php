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

class Testing_Campaign_CampaignFactory {

    /** @var Testing_TestExecution_TestExecutionCollectionFeeder */
    private $collection_factory;

    public function __construct(Testing_TestExecution_TestExecutionCollectionFeeder $collection_feeder) {
        $this->collection_feeder = $collection_feeder;
    }

    /**
     * @return Testing_Campaign_Campaign
     */
    public function getInstanceFromRow(Project $project, $row) {
        $list_of_test_executions = new Testing_TestExecution_TestExecutionCollection();
        if ($row['product_version_id']) {
            $release = new Testing_Release_ArtifactRelease($row['product_version_id']);
        } else {
            $release = new Testing_Release_NullRelease();
        }
        $campaign = new Testing_Campaign_Campaign($row['id'], $project, $row['name'], $release, $list_of_test_executions);
        $this->collection_feeder->feedCollection($campaign, $list_of_test_executions);

        return $campaign;
    }
}
