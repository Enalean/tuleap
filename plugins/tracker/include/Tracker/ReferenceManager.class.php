<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
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

require_once 'common/reference/ReferenceManager.class.php';

/**
 * I'm responsible of managing Tracker related cross references
 */
class Tracker_ReferenceManager {
    private $reference_manager;

    public function __construct(ReferenceManager $reference_manager) {
        $this->reference_manager = $reference_manager;
    }

    /**
     * Create a cross reference on $source_artifact that point on $target_artifact
     *
     * @param Tracker_Artifact $source_artifact
     * @param Tracker_Artifact $target_artifact
     * @param PFUser $user
     *
     * @return CrossReference
     */
    public function getCrossReferenceBetweenTwoArtifacts(Tracker_Artifact $source_artifact, Tracker_Artifact $target_artifact, PFUser $user) {
        return new CrossReference(
            $source_artifact->getId(),
            $source_artifact->getTracker()->getGroupId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $source_artifact->getTracker()->getItemname(),
            $target_artifact->getId(),
            $target_artifact->getTracker()->getGroupId(),
            Tracker_Artifact::REFERENCE_NATURE,
            $target_artifact->getTracker()->getItemname(),
            $user->getId()
        );
    }

    /**
     * Save in database a cross reference between $source_artifact and $target_artifact
     *
     * @param Tracker_Artifact $source_artifact
     * @param Tracker_Artifact $target_artifact
     * @param PFUser $user
     */
    public function insertBetweenTwoArtifacts(Tracker_Artifact $source_artifact, Tracker_Artifact $target_artifact, PFUser $user) {
        $this->reference_manager->insertCrossReference(
            $this->getCrossReferenceBetweenTwoArtifacts($source_artifact, $target_artifact, $user)
        );
    }

    /**
     * Remove from database a cross reference between $source_artifact and $target_artifact
     *
     * @param Tracker_Artifact $source_artifact
     * @param Tracker_Artifact $target_artifact
     * @param PFUser $user
     */
    public function removeBetweenTwoArtifacts(Tracker_Artifact $source_artifact, Tracker_Artifact $target_artifact, PFUser $user) {
        $this->reference_manager->removeCrossReference(
            $this->getCrossReferenceBetweenTwoArtifacts($source_artifact, $target_artifact, $user)
        );
    }
}

?>
