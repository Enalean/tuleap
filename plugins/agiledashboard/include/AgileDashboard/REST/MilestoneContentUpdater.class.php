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

    public function __construct(Tracker_FormElementFactory $form_element_factory) {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * User want to update the content of a given milestone
     *
     * @param array            $linked_artifact_ids  The ids of the artifacts to link
     * @param PFUser           $current_user         The user who made the link
     * @param Tracker_Artifact $artifact             The milestone
     *
     */
    public function updateMilestoneContent(array $linked_artifact_ids, PFUser $current_user, Tracker_Artifact $artifact) {
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());
        if (! count($artlink_fields)) {
            return;
        }

        $fields_data = $this->getFieldsDataForNewChangeset(
            $artlink_fields[0],
            $artifact,
            $current_user,
            $linked_artifact_ids
        );

        $this->unlinkAndLinkElements($artifact, $fields_data, $current_user, $linked_artifact_ids);
    }

    private function getFieldsDataForNewChangeset(
        Tracker_FormElement_Field_ArtifactLink $artlink_field,
        Tracker_Artifact $artifact,
        PFUser $current_user,
        array $linked_artifact_ids
    ) {
        $fields_data             = array();
        $elements_already_linked = $this->getMilestoneContentItemsAlreadyLinked(
            $artifact->getLinkedArtifacts($current_user)
        );
        $unlinked_elements       = $this->unlinkedMilestoneContentItems(
            $artifact,
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
            $artifact->createNewChangeset($fields_data, '', $current_user, '');
        } catch (Tracker_NoChangeException $exception) {
            //Do nothing. Just need to reorder the items
        } catch (Exception $exception) {
            return false;
        }

        $this->setOrder($linked_artifact_ids);
    }

    private function setOrder(array $linked_artifact_ids) {
        $dao         = new Tracker_Artifact_PriorityDao();
        $predecessor = null;

        foreach ($linked_artifact_ids as $linked_artifact_id) {
            $dao->moveArtifactAfter($linked_artifact_id, $predecessor);
            $predecessor = $linked_artifact_id;
        }
    }

    private function formatLinkedElementForNewChangeset(array $linked_elements) {
        return implode(',', $linked_elements);
    }

    /**
     * Returns the list of content items which will be unlinked to the milestone
     *
     * @param Tracker_Artifact $artifact              The milestone
     * @param array            $unliked_artifact_ids  The ids of the artifacts to unlink
     * @param array            $linked_artifact_ids   The ids of the artifacts which will be linked
     *
     * @return bool true if success false otherwise
     */
    private function unlinkedMilestoneContentItems(Tracker_Artifact $artifact, array $unliked_artifact_ids, array $linked_artifact_ids) {
        $artlink_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());
        $removed_values = array();

        if (count($artlink_fields)) {
            foreach($unliked_artifact_ids as $unliked_artifact_id) {
                if (! in_array($unliked_artifact_id, $linked_artifact_ids)) {
                    $removed_values[$unliked_artifact_id] = 1;
                }
            }
        }

        return $removed_values;
    }

}
