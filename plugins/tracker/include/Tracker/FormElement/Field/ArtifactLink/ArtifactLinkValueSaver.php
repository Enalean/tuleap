<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_Value_ArtifactLinkDao;
use Tracker_ReferenceManager;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker;
use Tracker_Artifact_ChangesetValue;
use PFUser;

class ArtifactLinkValueSaver {

    /**
     * @var SourceOfAssociationDetector
     */
    private $source_of_association_detector;

    /**
     * @var Tracker_ReferenceManager
     */
    private $reference_manager;

    /**
     * @var Tracker_FormElement_Field_Value_ArtifactLinkDao
     */
    private $dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElement_Field_Value_ArtifactLinkDao $dao,
        Tracker_ReferenceManager $reference_manager,
        SourceOfAssociationDetector $source_of_association_detector
    ) {
        $this->artifact_factory               = $artifact_factory;
        $this->dao                            = $dao;
        $this->reference_manager              = $reference_manager;
        $this->source_of_association_detector = $source_of_association_detector;
    }

    /**
     * Save the value
     *
     * @param PFUser                          $user                    The current user
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param mixed                           $submitted_value         The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     */
    public function saveValue(
        Tracker_FormElement_Field_ArtifactLink $field,
        PFUser $user,
        Tracker_Artifact $artifact,
        $changeset_value_id,
        array $submitted_value,
        Tracker_Artifact_ChangesetValue $previous_changesetvalue = null
    ) {
        foreach ($this->getArtifactIdsToLink($artifact, $submitted_value, $previous_changesetvalue) as $artifact_to_be_linked_by_tracker) {
            $tracker = $artifact_to_be_linked_by_tracker['tracker'];
            $nature  = $this->getNature($field->getTracker(), $tracker);

            $this->dao->create(
                $changeset_value_id,
                $nature,
                $artifact_to_be_linked_by_tracker['ids'],
                $tracker->getItemName(),
                $tracker->getGroupId()
            );
        }

        return $this->updateCrossReferences($user, $artifact, $submitted_value);
    }

    /**
     * Verify (and update if needed) that the link between what submitted the user ($submitted_values) and
     * the current artifact is correct resp. the association definition.
     *
     * Given I defined following hierarchy:
     * Release
     * `-- Sprint
     *
     * If $artifact is a Sprint and I try to link a Release, this method detect
     * it and update the corresponding Release with a link toward current sprint
     *
     * @param Tracker_Artifact           $artifact
     * @param mixed                      $submitted_value
     * @param Tracker_Artifact_Changeset $previous_changesetvalue
     *
     * @return mixed The submitted value expurged from updated links
     */
    public function updateLinkingDirection(
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $artifact,
        array $submitted_value,
        Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $artifacts = $this->getArtifactsFromChangesetValue($submitted_value, $previous_changesetvalue);

        $artifact_id_already_linked = array();
        foreach ($artifacts as $artifact_to_add) {
            if ($this->source_of_association_detector->isChild($artifact_to_add, $artifact)) {
                $source_of_association_collection->add($artifact_to_add);
                $artifact_id_already_linked[] = $artifact_to_add->getId();
            }
        }

        return $this->removeArtifactsFromSubmittedValue($submitted_value, $artifact_id_already_linked);
    }

    /**
     * Remove from user submitted artifact links the artifact ids that where already
     * linked after the direction checking
     *
     * Should be private to the class but almost impossible to test in the context
     * of saveNewChangeset.
     *
     * @param Array $submitted_value
     * @param Array $artifact_id_already_linked
     *
     * @return Array
     */
    private function removeArtifactsFromSubmittedValue(array $submitted_value, array $artifact_id_already_linked) {
        $new_values = explode(',', $submitted_value['new_values']);
        $new_values = array_map('trim', $new_values);
        $new_values = array_diff($new_values, $artifact_id_already_linked);
        $submitted_value['new_values'] = implode(',', $new_values);

        return $submitted_value;
    }

    private function getArtifactsFromChangesetValue(
        array $submitted_value,
        Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $new_values     = (string)$submitted_value['new_values'];
        $removed_values = isset($submitted_value['removed_values']) ? $submitted_value['removed_values'] : array();
        // this array will be the one to save in the new changeset
        $artifact_ids = array();
        if ($previous_changesetvalue != null) {
            $artifact_ids = $previous_changesetvalue->getArtifactIds();
            // We remove artifact links that user wants to remove
            if (is_array($removed_values) && ! empty($removed_values)) {
                $artifact_ids = array_diff($artifact_ids, array_keys($removed_values));
            }
        }

        if (trim($new_values) != '') {
            $new_artifact_ids = array_diff(explode(',', $new_values), array_keys($removed_values));
            // We add new links to existing ones
            foreach ($new_artifact_ids as $new_artifact_id) {
                if ( ! in_array($new_artifact_id, $artifact_ids)) {
                    $artifact_ids[] = (int)$new_artifact_id;
                }
            }
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifact_ids);
    }

    private function getNature(Tracker $from_tracker, Tracker $to_tracker) {
        if (in_array($to_tracker, $from_tracker->getChildren())) {
            return Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        }

        return null;
    }

    /**
     * Update cross references of this field
     *
     * @param Tracker_Artifact $artifact the artifact that is currently updated
     * @param array            $submitted_value   the array of added and removed artifact links ($values['added_values'] is a string and $values['removed_values'] is an array of artifact ids
     *
     * @return boolean
     */
    private function updateCrossReferences(PFUser $user, Tracker_Artifact $artifact, array $submitted_value) {
        $update_ok = true;

        foreach ($this->getAddedArtifactIds($submitted_value) as $added_artifact_id) {
            $update_ok = $update_ok && $this->insertCrossReference($user, $artifact, $added_artifact_id);
        }
        foreach ($this->getRemovedArtifactIds($submitted_value) as $removed_artifact_id) {
            $update_ok = $update_ok && $this->removeCrossReference($user, $artifact, $removed_artifact_id);
        }

        return $update_ok;
    }

    private function canLinkArtifacts(Tracker_Artifact $src_artifact, Tracker_Artifact $artifact_to_link) {
        return ($src_artifact->getId() != $artifact_to_link->getId()) && $artifact_to_link->getTracker();
    }

    private function getAddedArtifactIds(array $values) {
        if (array_key_exists('new_values', $values)) {
            if (trim($values['new_values']) != '') {
                return array_map('intval', explode(',', $values['new_values']));
            }
        }
        return array();
    }

    private function getRemovedArtifactIds(array $values) {
        if (array_key_exists('removed_values', $values)) {
            return array_map('intval', array_keys($values['removed_values']));
        }
        return array();
    }

    private function insertCrossReference(PFUser $user, Tracker_Artifact $source_artifact, $target_artifact_id) {
        return $this->reference_manager->insertBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

    private function removeCrossReference(PFUser $user, Tracker_Artifact $source_artifact, $target_artifact_id) {
        return $this->reference_manager->removeBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

    /** @return {'tracker' => Tracker, 'ids' => int[]}[] */
    private function getArtifactIdsToLink(
        Tracker_Artifact $artifact,
        $value,
        Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $all_artifacts_to_link = $this->getArtifactsFromChangesetValue(
            $value,
            $previous_changesetvalue
        );

        $all_artifact_to_be_linked = array();
        foreach ($all_artifacts_to_link as $artifact_to_link) {
            if ($this->canLinkArtifacts($artifact, $artifact_to_link)) {
                $tracker = $artifact_to_link->getTracker();

                if (! isset($all_artifact_to_be_linked[$tracker->getId()])) {
                    $all_artifact_to_be_linked[$tracker->getId()] = array(
                        'tracker' => $tracker,
                        'ids'     => array()
                    );
                }

                $all_artifact_to_be_linked[$tracker->getId()]['ids'][] = $artifact_to_link->getId();
            }
        }

        return $all_artifact_to_be_linked;
    }
}