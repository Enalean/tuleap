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

class Testing_Defect_DefectCollectionFeeder {

    /** @var Testing_Defect_DefectDao */
    private $dao;

    public function __construct(
        Testing_Defect_DefectDao $dao,
        Testing_Defect_DefectFactory $factory
    ) {
        $this->dao     = $dao;
        $this->factory = $factory;
    }

    public function feedCollection(Testing_TestExecution_TestExecution $execution, Testing_Defect_DefectCollection $collection) {
        foreach ($this->dao->searchByExecutionId($execution->getId()) as $row) {
            $defect = $this->factory->getInstanceFromRow($execution, $row);
            $collection->append($defect);
        }
    }
}
