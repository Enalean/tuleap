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

class Testing_Release_ReleaseParentOfCyclesInfoPresenterCollectionFactory {

    /** @var Tracker */
    private $release_tracker;

    /** @var PFUser */
    private $current_user;

    public function __construct(
        Project $project,
        Tracker $release_tracker,
        PFUser $current_user
    ) {
        $this->project         = $project;
        $this->release_tracker = $release_tracker;
        $this->current_user    = $current_user;
    }

    public function getPresenter() {
        $collection = new Testing_Release_ReleaseParentOfCyclesInfoPresenterCollection();
        foreach(Tracker_ArtifactFactory::instance()->getArtifactsByTrackerId($this->release_tracker->getId()) as $artifact) {
            $collection->append(
                new Testing_Release_ReleaseParentOfCyclesInfoPresenter(
                    $this->project,
                    new Testing_Release_ArtifactRelease($artifact->getId()),
                    $this->getListOfCycles($artifact)
                )
            );
        }
        return $collection;
    }

    private function getListOfCycles(Tracker_Artifact $artifact) {
        $list_of_cycles = new Testing_Release_ReleaseInfoPresenterCollection();
        foreach ($artifact->getChildrenForUser($this->current_user) as $child) {
            $cycle = new Testing_Release_ReleaseInfoPresenter(
                $this->project,
                new Testing_Release_ArtifactRelease($child->getId())
            );
            $list_of_cycles->append($cycle);
        }
        return $list_of_cycles;
    }
}
