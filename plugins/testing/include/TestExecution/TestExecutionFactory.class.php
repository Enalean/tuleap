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

class Testing_TestExecution_TestExecutionFactory {

    /** @var UserManager */
    private $user_manager;

    /** @var Testing_TestResult_TestResultCollectionFeeder */
    private $collection_feeder;

    public function __construct(
        UserManager $user_manager,
        Testing_TestResult_TestResultCollectionFeeder $collection_feeder
    ) {
        $this->collection_feeder = $collection_feeder;
        $this->user_manager      = $user_manager;
    }

    public function getInstanceFromRow(Testing_Campaign_Campaign $campaign, $row) {
        $user            = $this->user_manager->getUserById($row['assigned_to']);
        $list_of_results = new Testing_TestResult_TestResultCollection();

        $test_case         = new Testing_TestCase_TestCase($row['test_case_id']);
        $test_case_version = new Testing_TestCase_TestCaseVersion($row['test_version_id'], $test_case);

        $execution = new Testing_TestExecution_TestExecution($row['id'], $campaign, $test_case_version, $user, $list_of_results);
        $this->collection_feeder->feedCollection($execution, $list_of_results);

        return $execution;
    }
}
