<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class MilestoneContentUpdater {

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory) {
        $this->form_element_factory = $form_element_factory;
        $this->artifact_factory     = Tracker_ArtifactFactory::instance();
    }

    /**
     * User want to update the content of a given milestone
     *
     * @param array              $linked_artifact_ids  The ids of the artifacts to link
     * @param PFUser             $current_user         The user who made the link
     * @param Planning_Milestone $milestone            The milestone
     *
     */
    public function updateMilestoneContent(array $linked_artifact_ids, PFUser $current_user, Planning_Milestone $milestone) {
        $artifact       = $milestone->getArtifact();
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());

        if (! count($artlink_fields)) {
            return;
        }

        $fields_data = $this->getFieldsDataForNewChangeset(
            $artlink_fields[0],
            $milestone,
            $current_user,
            $linked_artifact_ids
        );

        $this->unlinkAndLinkElements($artifact, $fields_data, $current_user, $linked_artifact_ids);
    }

    private function getFieldsDataForNewChangeset(
        Tracker_FormElement_Field_ArtifactLink $artlink_field,
        Planning_Milestone $milestone,
        PFUser $current_user,
        array $linked_artifact_ids
    ) {
        $artifact                = $milestone->getArtifact();
        $fields_data             = array();
        $elements_already_linked = $this->getMilestoneContentItemsAlreadyLinked(
            $artifact->getLinkedArtifacts($current_user)
        );

        $unlinked_elements = $this->getMilestoneContentItemsToUnlink(
            $milestone,
            $elements_already_linked,
            $linked_artifact_ids
        );

        $linked_elements = array();
        foreach($linked_artifact_ids as $linked_artifact_id) {
            if (! in_array($linked_artifact_id, $elements_already_linked)) {
                $linked_elements[] = $linked_artifact_id;
            }
        }

        $formated_linked_elements = $this->formatLinkedElementForNewChangeset($linked_elements);

        $fields_data[$artlink_field->getId()]['removed_values'] = $unlinked_elements;
        $fields_data[$artlink_field->getId()]['new_values']     = $formated_linked_elements;

        return $fields_data;
    }

    private function getMilestoneContentItemsAlreadyLinked(array $artifacts) {
        return array_map(
            function (Tracker_Artifact $artifact) {
                return $artifact->getId();
            },
            $artifacts
        );
    }

    private function unlinkAndLinkElements(Tracker_Artifact $artifact, array $fields_data, PFUser $current_user, array $linked_artifact_ids) {
        try {
            $artifact->createNewChangeset($fields_data, '', $current_user);
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing. Just need to reorder the items
        } catch (Exception $exception) {
            return false;
        }

        $this->setOrder($linked_artifact_ids);
    }

    public function setOrder(array $linked_artifact_ids) {
        $dao         = new Tracker_Artifact_PriorityDao();
        $predecessor = null;

        foreach ($linked_artifact_ids as $linked_artifact_id) {
            if (isset($predecessor)) {
                $dao->moveArtifactAfter($linked_artifact_id, $predecessor);
            }
            $predecessor = $linked_artifact_id;
        }
    }

    private function formatLinkedElementForNewChangeset(array $linked_elements) {
        return implode(',', $linked_elements);
    }

    /**
     * Returns the list of content items which will be unlinked to the milestone
     *
     * @param Planning_Milestone $milestone               The milestone
     * @param array              $elements_already_linked The ids of the artifacts already linked to the milestone
     * @param array              $linked_artifact_ids     The ids of the artifacts which will be linked
     *
     * @return bool true if success false otherwise
     */
    private function getMilestoneContentItemsToUnlink(
        Planning_Milestone $milestone,
        array $elements_already_linked,
        array $linked_artifact_ids
    ) {
        $artifact       = $milestone->getArtifact();
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());
        $removed_values = array();

        $content_trackers_ids = $milestone->getPlanning()->getBacklogTrackersIds();

        if (count($artlink_fields)) {
            foreach($elements_already_linked as $artifact_already_linked_id) {
                $unlinked_artifact = $this->artifact_factory->getArtifactById($artifact_already_linked_id);

                if ($this->artifactAlreadyLinkedIsNotInTheNewListSentByRESTAndIsInBacklogTracker(
                        $linked_artifact_ids,
                        $artifact_already_linked_id,
                        $unlinked_artifact,
                        $content_trackers_ids
                    )
                ) {
                    $removed_values[$artifact_already_linked_id] = 1;
                }
            }
        }

        return $removed_values;
    }

    private function artifactAlreadyLinkedIsNotInTheNewListSentByRESTAndIsInBacklogTracker(
        array $linked_artifact_ids,
        $artifact_already_linked_id,
        $unlinked_artifact,
        array $content_trackers_ids
    ) {
        return ! in_array($artifact_already_linked_id, $linked_artifact_ids)
               && $this->artifactIsInBacklogTracker($unlinked_artifact, $content_trackers_ids);
    }

    private function artifactIsInBacklogTracker(Tracker_Artifact $artifact, $backlog_tracker_ids) {
        foreach ($backlog_tracker_ids as $backlog_tracker_id) {
            if ($artifact->getTrackerId() == $backlog_tracker_id) {
                return true;
            }
        }

        return false;
    }

}
