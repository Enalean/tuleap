<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use \Tracker_FormElementFactory;
use \Tracker_FormElement_Field_ArtifactLink;
use \Planning_MilestoneFactory;
use \Planning_Milestone;
use \PFUser;

class MilestoneSubMilestonesUpdater {

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var Planning_MilestoneFactory */
    private $milestone_factory;

    /** @var ArtifactLinkUpdater */
    private $artifactlink_updater;

    public function __construct(Tracker_FormElementFactory $form_element_factory, Planning_MilestoneFactory $milestone_factory, ArtifactLinkUpdater $artifactlink_updater) {
        $this->form_element_factory     = $form_element_factory;
        $this->milestone_factory        = $milestone_factory;
        $this->artifactlink_updater     = $artifactlink_updater;
    }

    /**
     * User want to update the submilestones of a given milestone
     *
     * @param array              $new_submilestones_ids  The ids of the artifacts to link
     * @param Planning_Milestone $milestone            The milestone
     * @param PFUser             $current_user         The user who made the link
     *
     */
    public function updateMilestoneSubMilestones(array $new_submilestones_ids, Planning_Milestone $milestone, PFUser $current_user) {
        $artifact              = $milestone->getArtifact();
        $artlink_fields        = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());

        if (! count($artlink_fields)) {
            return;
        }

        $fields_data = $this->getFieldsDataForNewChangeset(
            $artlink_fields[0],
            $milestone,
            $current_user,
            $new_submilestones_ids
        );

        $this->artifactlink_updater->unlinkAndLinkElements($artifact, $fields_data, $current_user, $new_submilestones_ids);
    }

    private function getFieldsDataForNewChangeset(
        Tracker_FormElement_Field_ArtifactLink $artlink_field,
        Planning_Milestone $milestone,
        PFUser $current_user,
        array $new_submilestones_ids
    ) {
        $artifact                = $milestone->getArtifact();
        $elements_already_linked = $this->artifactlink_updater->getElementsAlreadyLinkedToMilestone($artifact, $current_user);

        $submilestones_to_be_unlinked = $this->getAllSubmilestonesToBeRemoved(
            $current_user,
            $milestone,
            $elements_already_linked,
            $new_submilestones_ids
        );

        $submilestones_to_be_linked = $this->artifactlink_updater->getElementsToLink(
            $elements_already_linked,
            $new_submilestones_ids
        );

        return $this->artifactlink_updater->formatFieldDatas($artlink_field, $submilestones_to_be_linked, $submilestones_to_be_unlinked);
    }

    private function getAllSubmilestonesToBeRemoved(PFUser $user, Planning_Milestone $milestone, array $elements_already_linked, array $new_submilestones_ids) {
        $artifacts_to_be_removed =$this->getAllLinkedArtifactsThatShouldBeRemoved($elements_already_linked, $new_submilestones_ids);

        return $this->filterArtifactsThatAreValidSubmilestones($user, $milestone, $artifacts_to_be_removed);
    }

    private function getAllLinkedArtifactsThatShouldBeRemoved(array $elements_already_linked, array $new_submilestones_ids) {
        return array_diff($elements_already_linked, $new_submilestones_ids);
    }

    private function filterArtifactsThatAreValidSubmilestones(PFUser $user, Planning_Milestone $milestone, array $artifacts_to_be_removed) {
        $submilestones = array();

        foreach ($artifacts_to_be_removed as $artifact_to_be_removed) {
            $candidate_submilestone = $this->milestone_factory->getBareMilestoneByArtifactId($user, $artifact_to_be_removed);

            if ($candidate_submilestone && $milestone->milestoneCanBeSubmilestone($candidate_submilestone)) {
                $submilestones[] = $candidate_submilestone->getArtifactId();
            }
        }

        return $submilestones;
    }

}
