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

class Testing_TestResult_TestResultCollectionFeeder {

    /** @var Testing_TestResult_TestResultDao */
    private $dao;

    public function __construct(
        Testing_TestResult_TestResultDao $dao,
        Testing_TestResult_TestResultFactory $factory
    ) {
        $this->dao     = $dao;
        $this->factory = $factory;
    }

    public function feedCollection(Testing_TestExecution_TestExecution $execution, Testing_TestResult_TestResultCollection $collection) {
        $rows = $this->dao->searchByExecutionId($execution->getId());
        if (! count($rows)) {
            $collection->append(new Testing_TestResult_TestResultNotRun());
            return;
        }

        foreach ($rows as $row) {
            $result = $this->factory->getInstanceFromRow($execution, $row);
            $collection->append($result);
        }
    }
}
