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

class Testing_Requirement_RequirementInfoCollectionPresenterFactory {

    public function __construct(
        Project $project,
        Tracker $requirement_tracker,
        Testing_Requirement_RequirementInfoPresenterFactory $factory,
        Testing_Release_ReleaseInfoPresenterCollectionFactory $release_collection_factory
    ) {
        $this->project                    = $project;
        $this->requirement_tracker        = $requirement_tracker;
        $this->factory                    = $factory;
        $this->release_collection_factory = $release_collection_factory;
    }

    public function getPresenter() {
        $create_requirement_form = new TestingFacadeTrackerCreationPresenter($this->requirement_tracker);

        return new Testing_Requirement_RequirementInfoCollectionPresenter(
            $this->project,
            $this->release_collection_factory->getPresenter(),
            $this->getListOfRequirementInfoPresenters(),
            $create_requirement_form
        );
    }

    public function getListOfRequirementInfoPresenters() {
        $list_of_requirement_info_presenters = array();
        foreach(Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($this->requirement_tracker->getId()) as $artifact) {
            $requirement = new Testing_Requirement_Requirement($artifact->getId());
            $list_of_requirement_info_presenters[] = $this->factory->getPresenter($requirement);
        }
        return $list_of_requirement_info_presenters;
    }
}
