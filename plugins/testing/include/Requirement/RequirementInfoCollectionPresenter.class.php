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

class Testing_Requirement_RequirementInfoCollectionPresenter {

    public $list_of_requirements;

    public function __construct(
        Project $project,
        Testing_Release_ReleaseInfoPresenterCollection $list_of_releases,
        array $list_of_requirements,
        TestingFacadeTrackerCreationPresenter $create_requirement_form
    ) {
        $this->list_of_releases     = $list_of_releases;
        $this->has_releases         = count($list_of_releases) > 0;
        $this->list_of_requirements = $list_of_requirements;
        $this->create_uri  = '/plugins/testing/?group_id='. $project->getId() .'&resource=requirement&action=create';
        $this->filter_uri  = '/plugins/testing/';

        $this->group_id = $project->getId();
        $this->create_requirement_form = $create_requirement_form;
        $this->current_release = HTTPRequest::instance()->get('release');
    }
}
