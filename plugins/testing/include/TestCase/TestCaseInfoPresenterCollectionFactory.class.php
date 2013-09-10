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

class Testing_TestCase_TestCaseInfoPresenterCollectionFactory {

    /** @var Tracker */
    private $testcase_tracker;

    public function __construct(
        Project $project,
        Tracker $testcase_tracker,
        Testing_Requirement_TestCaseAssociationDao $dao
    ) {
        $this->project          = $project;
        $this->testcase_tracker = $testcase_tracker;
        $this->dao              = $dao;
    }

    public function getPresenter() {
        $collection = new Testing_TestCase_TestCaseInfoPresenterCollection();
        $requirements_sum = $this->getRequirementsSum();
        foreach(Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($this->testcase_tracker->getId()) as $artifact) {
            $test_case = new Testing_TestCase_TestCase($artifact->getId());
            $nb_of_requirements = 0;
            if (isset($requirements_sum[$test_case->getId()])) {
                $nb_of_requirements = $requirements_sum[$test_case->getId()];
            }
            $collection->append(new Testing_TestCase_TestCaseInfoPresenter($this->project, $test_case, $nb_of_requirements));
        }
        return $collection;
    }

    private function getRequirementsSum() {
        $sum = array();
        foreach ($this->dao->searchForRequirementsSum($this->testcase_tracker->getId()) as $row) {
            $sum[$row['testversion_id']] = $row['nb'];
        }
        return $sum;
    }
}
