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
use Tracker_ArtifactLinkInfo;
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
     * @param Tracker_FormElement_Field_ArtifactLink $field              The field in which we save the value
     * @param PFUser                                 $user               The current user
     * @param Tracker_Artifact                       $artifact           The artifact
     * @param int                                    $changeset_value_id The id of the changeset_value
     * @param mixed                                  $submitted_value    The value submitted by the user
     */
    public function saveValue(
        Tracker_FormElement_Field_ArtifactLink $field,
        PFUser $user,
        Tracker_Artifact $artifact,
        $changeset_value_id,
        array $submitted_value
    ) {
        $artifact_ids_to_link = $this->getArtifactIdsToLink($field->getTracker(), $artifact, $submitted_value);
        foreach ($artifact_ids_to_link as $artifact_to_be_linked_by_tracker) {
            $tracker = $artifact_to_be_linked_by_tracker['tracker'];

            foreach ($artifact_to_be_linked_by_tracker['natures'] as $nature => $ids) {
                if (! $nature) {
                    $nature = null;
                }

                $this->dao->create(
                    $changeset_value_id,
                    $nature,
                    $ids,
                    $tracker->getItemName(),
                    $tracker->getGroupId()
                );
            }
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
        $submitted_value['list_of_artifactlinkinfo'] = $this->getListOfArtifactLinkInfo(
            $source_of_association_collection,
            $artifact,
            $submitted_value,
            $previous_changesetvalue
        );

        return $submitted_value;
    }

    /** @return Tracker_ArtifactLinkInfo[] */
    private function getListOfArtifactLinkInfo(
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $from_artifact,
        array $submitted_value,
        Tracker_Artifact_ChangesetValue_ArtifactLink $previous_changesetvalue = null
    ) {
        $list_of_artifactlinkinfo = array();
        if ($previous_changesetvalue != null) {
            $list_of_artifactlinkinfo = $previous_changesetvalue->getValue();
            $this->removeLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);
        }
        $this->addLinksFromSubmittedValue($list_of_artifactlinkinfo, $submitted_value);
        $this->removeAlreadyLinkedParentArtifacts(
            $source_of_association_collection,
            $from_artifact,
            $list_of_artifactlinkinfo
        );

        return $list_of_artifactlinkinfo;
    }

    private function removeAlreadyLinkedParentArtifacts(
        SourceOfAssociationCollection $source_of_association_collection,
        Tracker_Artifact $from_artifact,
        array &$list_of_artifactlinkinfo
    ) {
        foreach ($list_of_artifactlinkinfo as $id => $artifactinfo) {
            $artifact_to_add = $artifactinfo->getArtifact();
            if ($this->source_of_association_detector->isChild($artifact_to_add, $from_artifact)) {
                $source_of_association_collection->add($artifact_to_add);
                unset($list_of_artifactlinkinfo[$id]);
            }
        }
    }

    private function removeLinksFromSubmittedValue(
        array &$list_of_artifactlinkinfo,
        array $submitted_value
    ) {
        $removed_values = $this->extractRemovedValuesFromSubmittedValue($submitted_value);

        if (empty($removed_values)) {
            return;
        }

        foreach ($list_of_artifactlinkinfo as $id => $noop) {
            if (isset($removed_values[$id])) {
                unset($list_of_artifactlinkinfo[$id]);
            }
        }
    }

    private function addLinksFromSubmittedValue(array &$list_of_artifactlinkinfo, array $submitted_value) {
        $new_values = $this->extractNewValuesFromSubmittedValue($submitted_value);
        $nature     = $this->extractNatureFromSubmittedValue($submitted_value);

        foreach ($new_values as $new_artifact_id) {
            if (isset($list_of_artifactlinkinfo[$new_artifact_id])) {
                continue;
            }

            $artifact = $this->artifact_factory->getArtifactById($new_artifact_id);
            if (! $artifact) {
                continue;
            }

            $list_of_artifactlinkinfo[$new_artifact_id] = Tracker_ArtifactLinkInfo::buildFromArtifact(
                $artifact,
                $nature
            );
        }
    }

    private function extractNatureFromSubmittedValue(array $submitted_value) {
        if (isset($submitted_value['nature'])) {
            return $submitted_value['nature'];
        }

        return null;
    }

    private function extractNewValuesFromSubmittedValue(array $submitted_value) {
        $new_values          = (string)$submitted_value['new_values'];
        $removed_values      = $this->extractRemovedValuesFromSubmittedValue($submitted_value);
        $new_values_as_array = array_filter(array_map('intval', explode(',', $new_values)));

        return array_unique(array_diff($new_values_as_array, array_keys($removed_values)));
    }

    private function extractRemovedValuesFromSubmittedValue(array $submitted_value) {
        if (! isset($submitted_value['removed_values'])) {
            return array();
        }

        $removed_values = $submitted_value['removed_values'];
        if (! is_array($removed_values)) {
            return array();
        }

        return $removed_values;
    }

    private function getNature(Tracker_ArtifactLinkInfo $artifactlinkinfo, Tracker $from_tracker, Tracker $to_tracker) {
        if (in_array($to_tracker, $from_tracker->getChildren())) {
            return Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        }

        $existing_nature = $artifactlinkinfo->getNature();
        if (! empty($existing_nature)) {
            return $existing_nature;
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
        $ids = array();
        foreach ($values['list_of_artifactlinkinfo'] as $artifactlinkinfo) {
            $ids[] = (int) $artifactlinkinfo->getArtifactId();
        }

        return $ids;
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
        Tracker $from_tracker,
        Tracker_Artifact $artifact,
        array $submitted_value
    ) {
        $all_artifact_to_be_linked = array();
        foreach ($submitted_value['list_of_artifactlinkinfo'] as $artifactlinkinfo) {
            $artifact_to_link = $artifactlinkinfo->getArtifact();
            if ($this->canLinkArtifacts($artifact, $artifact_to_link)) {
                $tracker = $artifact_to_link->getTracker();
                $nature  = $this->getNature($artifactlinkinfo, $from_tracker, $tracker);

                if (! isset($all_artifact_to_be_linked[$tracker->getId()])) {
                    $all_artifact_to_be_linked[$tracker->getId()] = array(
                        'tracker' => $tracker,
                        'natures' => array()
                    );
                }

                if (! isset($all_artifact_to_be_linked[$tracker->getId()]['natures'][$nature])) {
                    $all_artifact_to_be_linked[$tracker->getId()]['natures'][$nature] = array();
                }

                $all_artifact_to_be_linked[$tracker->getId()]['natures'][$nature][] = $artifact_to_link->getId();
            }
        }

        return $all_artifact_to_be_linked;
    }
}