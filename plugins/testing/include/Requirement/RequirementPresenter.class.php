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

class Testing_Requirement_RequirementVersionPresenter {

    public function __construct(Project $project, Testing_Requirement_RequirementVersion $requirement_version, array $list_of_test_cases) {
        $this->id                 = $requirement_version->getRequirementId();
        $this->name               = $requirement_version->getName();
        $this->version            = $requirement_version->getVersionNumber();
        $this->list_of_test_cases = $list_of_test_cases;
        $this->has_test_cases     = count($list_of_test_cases) > 0;
        $this->edit_uri = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=edit&id='. $this->id;
    }
}
