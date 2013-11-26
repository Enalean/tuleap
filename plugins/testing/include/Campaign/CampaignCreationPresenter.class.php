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

class Testing_Campaign_CampaignCreationPresenter {

    public function __construct(
        Project $project,
        Testing_Release_ReleaseParentOfCyclesInfoPresenterCollection $list_of_releases,
        Testing_TestCase_TestCaseInfoPresenterCollection $list_of_test_cases,
        array $list_of_requirements
    ) {
        $this->list_of_requirements = $list_of_requirements;
        $this->list_of_test_cases   = $list_of_test_cases;
        $this->list_of_releases     = $list_of_releases;
        $this->create_uri = '/plugins/testing/?group_id='. $project->getId() .'&resource=campaign&action=create';
    }
}
