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

class Testing_TestCase_TestCasePresenter {

    public function __construct(
        Project $project,
        Testing_TestCase_TestCase $testcase,
        array $list_of_requirements,
        array $list_of_available_requirements,
        TestingFacadeTrackerCreationPresenter $create_requirement_form
    ) {
        $this->id   = $testcase->getId();
        $this->name = $testcase->getName();

        $this->list_of_requirements           = $list_of_requirements;
        $this->has_requirements               = count($list_of_requirements) > 0;
        $this->list_of_available_requirements = $list_of_available_requirements;
        $this->has_available_requirements     = count($this->list_of_available_requirements) > 0;
        $this->link_requirement_uri = '/plugins/testing/?group_id='. $project->getId() .'&resource=testcase&action=link-requirement&id='. $this->id;
        $this->add_requirement_uri  = '/plugins/testing/?group_id='. $project->getId() .'&resource=testcase&action=add-requirement&id='. $this->id;
        $this->create_requirement_form = $create_requirement_form;

        $this->show_uri = '/plugins/testing/?group_id='. $project->getId() .'&resource=testcase&action=show&id='. $this->id;
    }
}
