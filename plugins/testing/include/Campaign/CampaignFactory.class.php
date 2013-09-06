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

    /** @var Testing_TestExecution_TestExecutionCollectionFactory */
    private $collection_factory;

    public function __construct(Testing_TestExecution_TestExecutionCollectionFactory $collection_factory) {
        $this->collection_factory = $collection_factory;
    }

    /**
     * @return Testing_Campaign_Campaign
     */
    public function getInstanceFromRow(Project $project, $row) {
        $list_of_test_executions = $this->collection_factory->getTestExecutionCollection($row['id']);
        return new Testing_Campaign_Campaign($row['id'], $project, $row['name'], $list_of_test_executions);
    }
}
