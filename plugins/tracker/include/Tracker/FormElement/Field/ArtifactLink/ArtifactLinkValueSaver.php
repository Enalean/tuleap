<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

use EventManager;
use Tracker_Artifact;
use Tracker_ArtifactLinkInfo;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_Value_ArtifactLinkDao;
use Tracker_ReferenceManager;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker;
use Feedback;
use PFUser;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;

class ArtifactLinkValueSaver
{

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

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ArtifactLinksUsageDao
     */
    private $artifact_links_usage_dao;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElement_Field_Value_ArtifactLinkDao $dao,
        Tracker_ReferenceManager $reference_manager,
        EventManager $event_manager,
        ArtifactLinksUsageDao $artifact_links_usage_dao
    ) {
        $this->artifact_factory         = $artifact_factory;
        $this->dao                      = $dao;
        $this->reference_manager        = $reference_manager;
        $this->event_manager            = $event_manager;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
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

    private function getNature(
        Tracker_Artifact $from_artifact,
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        Tracker $to_tracker,
        array $submitted_value
    ) {
        $existing_nature     = $artifactlinkinfo->getNature();
        $nature_by_hierarchy = $this->getNatureDefinedByHierarchy(
            $artifactlinkinfo,
            $from_tracker,
            $to_tracker,
            $existing_nature
        );
        if ($nature_by_hierarchy !== null) {
            return $nature_by_hierarchy;
        }

        $linked_artifact  = $artifactlinkinfo->getArtifact();
        if ($linked_artifact === null) {
            return null;
        }
        $nature_by_plugin = $this->getNatureDefinedByPlugin(
            $artifactlinkinfo,
            $from_artifact,
            $linked_artifact,
            $existing_nature,
            $submitted_value
        );

        if (! empty($nature_by_plugin)) {
            return $nature_by_plugin;
        }

        if (! empty($existing_nature)) {
            return $existing_nature;
        }

        return null;
    }

    private function getNatureDefinedByHierarchy(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        Tracker $to_tracker,
        $existing_nature
    ) {
        $is_child = $this->isTrackerChildrenOfTheOtherTracker($to_tracker, $from_tracker);

        if ($is_child) {
            if (
                $this->artifact_links_usage_dao->isTypeDisabledInProject(
                    $from_tracker->getProject()->getID(),
                    Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
                )
            ) {
                return Tracker_FormElement_Field_ArtifactLink::NO_NATURE;
            }

            $this->warnForceUsageOfChildType($artifactlinkinfo, $from_tracker, $existing_nature);

            return Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD;
        }

        if (
            $from_tracker->getChildren()
            && ! $is_child
            && $existing_nature === Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
        ) {
            $this->warnForceUsageOfNoneType($artifactlinkinfo, $from_tracker);

            return Tracker_FormElement_Field_ArtifactLink::NO_NATURE;
        }

        return null;
    }

    private function isTrackerChildrenOfTheOtherTracker(Tracker $to_tracker, Tracker $from_tracker): bool
    {
        foreach ($from_tracker->getChildren() as $child_tracker) {
            if ((int) $child_tracker->getId() === (int) $to_tracker->getId()) {
                return true;
            }
        }

        return false;
    }

    private function warnForceUsageOfNoneType(Tracker_ArtifactLinkInfo $artifactlinkinfo, Tracker $from_tracker)
    {
        if ($from_tracker->isProjectAllowedToUseNature()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'no_child',
                    $artifactlinkinfo->getArtifactId()
                )
            );
        }
    }

    private function warnForceUsageOfChildType(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        $existing_nature
    ) {
        if (
            $existing_nature !== Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
            && $from_tracker->isProjectAllowedToUseNature()
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'force_child',
                    $artifactlinkinfo->getArtifactId()
                )
            );
        }
    }

    private function getNatureDefinedByPlugin(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker_Artifact $from_artifact,
        Tracker_Artifact $to_artifact,
        $existing_nature,
        array $submitted_value
    ) {
        $nature_by_plugin = null;
        $this->event_manager->processEvent(TRACKER_EVENT_ARTIFACT_LINK_NATURE_REQUESTED, array(
            'project_id'      => $artifactlinkinfo->getGroupId(),
            'to_artifact'     => $to_artifact,
            'submitted_value' => $submitted_value,
            'nature'          => &$nature_by_plugin
        ));

        if (! empty($nature_by_plugin) && $existing_nature !== $nature_by_plugin) {
            $this->warnOverrideOfExistingNature(
                $artifactlinkinfo,
                $from_artifact->getTracker(),
                $existing_nature,
                $nature_by_plugin
            );
        }
        return $nature_by_plugin;
    }

    private function warnOverrideOfExistingNature(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        $existing_nature,
        $nature_by_plugin
    ) {
        if ($from_tracker->isProjectAllowedToUseNature()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'override_nature',
                    array($artifactlinkinfo->getArtifactId(), $existing_nature, $nature_by_plugin)
                )
            );
        }
    }

    /**
     * Update cross references of this field
     *
     * @param Tracker_Artifact $artifact the artifact that is currently updated
     * @param array            $submitted_value   the array of added and removed artifact links ($values['added_values'] is a string and $values['removed_values'] is an array of artifact ids
     *
     * @return boolean
     */
    private function updateCrossReferences(PFUser $user, Tracker_Artifact $artifact, array $submitted_value)
    {
        $update_ok = true;

        foreach ($this->getAddedArtifactIds($submitted_value) as $added_artifact_id) {
            $update_ok = $update_ok && $this->insertCrossReference($user, $artifact, $added_artifact_id);
        }
        foreach ($this->getRemovedArtifactIds($submitted_value) as $removed_artifact_id) {
            $update_ok = $update_ok && $this->removeCrossReference($user, $artifact, $removed_artifact_id);
        }

        return $update_ok;
    }

    private function canLinkArtifacts(Tracker_Artifact $src_artifact, Tracker_Artifact $artifact_to_link)
    {
        return ($src_artifact->getId() != $artifact_to_link->getId()) && $artifact_to_link->getTracker();
    }

    private function getAddedArtifactIds(array $values)
    {
        $ids = array();
        foreach ($values['list_of_artifactlinkinfo'] as $artifactlinkinfo) {
            $ids[] = (int) $artifactlinkinfo->getArtifactId();
        }

        return $ids;
    }

    private function getRemovedArtifactIds(array $values)
    {
        if (array_key_exists('removed_values', $values)) {
            return array_map('intval', array_keys($values['removed_values']));
        }
        return array();
    }

    private function insertCrossReference(PFUser $user, Tracker_Artifact $source_artifact, $target_artifact_id)
    {
        return $this->reference_manager->insertBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

    private function removeCrossReference(PFUser $user, Tracker_Artifact $source_artifact, $target_artifact_id)
    {
        return $this->reference_manager->removeBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

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
                $nature  = $this->getNature($artifact, $artifactlinkinfo, $from_tracker, $tracker, $submitted_value);

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
