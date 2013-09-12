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

class Testing_Requirement_RequirementPresenter {

    public function __construct(
        Project $project,
        Testing_Requirement_Requirement $requirement,
        array $list_of_test_cases,
        array $list_of_releases,
        array $list_of_available_test_cases,
        array $list_of_available_releases,
        TestingFacadeTrackerCreationPresenter $create_testcase_form
    ) {
        $this->id                           = $requirement->getId();
        $this->name                         = $requirement->getName();
        $this->list_of_test_cases           = $list_of_test_cases;
        $this->list_of_releases             = $list_of_releases;
        $this->has_test_cases               = count($list_of_test_cases) > 0;
        $this->list_of_available_test_cases = $list_of_available_test_cases;
        $this->has_available_test_cases     = count($this->list_of_available_test_cases) > 0;
        $this->list_of_available_releases   = $list_of_available_releases;
        $this->has_available_releases       = count($this->list_of_available_releases) > 0;
        $this->uses_releases                = $this->has_available_releases || count($this->list_of_releases) > 0;
        $this->edit_uri           = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=edit&id='. $this->id;
        $this->link_test_case_uri = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=link-test-case&id='. $this->id;
        $this->link_release_uri   = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=link-release&id='. $this->id;
        $this->add_test_case_uri  = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=add-test-case&id='. $this->id;
        $this->create_testcase_form = $create_testcase_form;
    }
}
