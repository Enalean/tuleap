<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Feedback;
use PFUser;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_ReferenceManager;
use Tracker_Workflow_Trigger_RulesManager;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Tracker;

class ArtifactLinkValueSaver
{
    /**
     * Request a custom type from other plugins for a new artifact link
     *
     * Parameters:
     * 'project_id'      => The id of the target project
     * 'to_artifact'     => The artifact linked to
     * 'submitted_value' => Values from the artifact form
     *
     * Expected results:
     * 'nature'          => string the type proposed by the plugin
     */
    public final const TRACKER_EVENT_ARTIFACT_LINK_TYPE_REQUESTED = 'tracker_event_artifact_link_type_requested';

    /**
     * @var Tracker_ReferenceManager
     */
    private $reference_manager;

    /**
     * @var ArtifactLinkFieldValueDao
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

    /**
     * @var Tracker_Workflow_Trigger_RulesManager
     */
    private $rules_manager;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        ArtifactLinkFieldValueDao $dao,
        Tracker_ReferenceManager $reference_manager,
        EventManager $event_manager,
        ArtifactLinksUsageDao $artifact_links_usage_dao,
        Tracker_Workflow_Trigger_RulesManager $rules_manager,
    ) {
        $this->artifact_factory         = $artifact_factory;
        $this->dao                      = $dao;
        $this->reference_manager        = $reference_manager;
        $this->event_manager            = $event_manager;
        $this->artifact_links_usage_dao = $artifact_links_usage_dao;
        $this->rules_manager            = $rules_manager;
    }

    /**
     * Save the value
     *
     * @param ArtifactLinkField $field              The field in which we save the value
     * @param PFUser                                 $user               The current user
     * @param Artifact                               $artifact           The artifact
     * @param int                                    $changeset_value_id The id of the changeset_value
     * @param mixed                                  $submitted_value    The value submitted by the user
     */
    public function saveValue(
        ArtifactLinkField $field,
        PFUser $user,
        Artifact $artifact,
        $changeset_value_id,
        array $submitted_value,
    ) {
        $artifact_ids_to_link = $this->getArtifactIdsToLink($field->getTracker(), $artifact, $submitted_value);
        foreach ($artifact_ids_to_link as $artifact_to_be_linked_by_type) {
            foreach ($artifact_to_be_linked_by_type as $type => $ids) {
                if (! $type) {
                    $type = null;
                }

                $this->dao->create(
                    $changeset_value_id,
                    $type,
                    $ids,
                );
            }
        }

        return $this->updateCrossReferences($user, $artifact, $submitted_value);
    }

    private function getType(
        Artifact $from_artifact,
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        Tracker $to_tracker,
        array $submitted_value,
    ) {
        $existing_type     = $artifactlinkinfo->getType();
        $type_by_hierarchy = $this->getTypeDefinedByHierarchy(
            $artifactlinkinfo,
            $from_tracker,
            $to_tracker,
            $existing_type
        );
        if ($type_by_hierarchy !== null) {
            return $type_by_hierarchy;
        }

        $linked_artifact = $artifactlinkinfo->getArtifact();
        if ($linked_artifact === null) {
            return null;
        }
        $type_by_plugin = $this->getTypeDefinedByPlugin(
            $artifactlinkinfo,
            $from_artifact,
            $linked_artifact,
            $existing_type,
            $submitted_value
        );

        if (! empty($type_by_plugin)) {
            return $type_by_plugin;
        }

        if (! empty($existing_type)) {
            return $existing_type;
        }

        return null;
    }

    /**
     * If project does not use the artifact link types yet, _is_child must continue
     * to be automatically set to that when an admin will enable the types everything
     * will be consistent
     */
    private function getTypeDefinedByHierarchy(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        Tracker $to_tracker,
        ?string $existing_type,
    ): ?string {
        $is_child  = $this->isTrackerChildrenOfTheOtherTracker($to_tracker, $from_tracker);
        $is_parent = $this->isTrackerChildrenOfTheOtherTracker($from_tracker, $to_tracker);

        if ($this->artifact_links_usage_dao->isProjectUsingArtifactLinkTypes((int) $from_tracker->getProject()->getID())) {
            if (
                ($is_child && $existing_type !== ArtifactLinkField::TYPE_IS_CHILD) ||
                $is_parent
            ) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext('tuleap-tracker', 'There is a hierarchy defined between "%s" and "%s" but the link between this artifact and %s is not parent/child.'),
                        $from_tracker->getName(),
                        $to_tracker->getName(),
                        $artifactlinkinfo->getLabel()
                    )
                );
            }

            if (
                ! $is_child &&
                $existing_type === ArtifactLinkField::TYPE_IS_CHILD &&
                $this->rules_manager->getForTargetTracker($from_tracker)->count() > 0
            ) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::WARN,
                    sprintf(
                        dgettext('tuleap-tracker', 'Artifact %s was linked as child of the current artifact but is not part of hierarchy. It will not be taken into account in triggers.'),
                        $artifactlinkinfo->getLabel()
                    )
                );
            }

            return null;
        }

        if ($is_child) {
            return ArtifactLinkField::TYPE_IS_CHILD;
        }

        if (
            $from_tracker->getChildren()
            && ! $is_child
            && $existing_type === ArtifactLinkField::TYPE_IS_CHILD
        ) {
            return ArtifactLinkField::NO_TYPE;
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

    private function getTypeDefinedByPlugin(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Artifact $from_artifact,
        Artifact $to_artifact,
        $existing_type,
        array $submitted_value,
    ) {
        $type_by_plugin = null;
        $this->event_manager->processEvent(self::TRACKER_EVENT_ARTIFACT_LINK_TYPE_REQUESTED, [
            'project_id'      => $artifactlinkinfo->getGroupId(),
            'to_artifact'     => $to_artifact,
            'submitted_value' => $submitted_value,
            'type'            => &$type_by_plugin,
        ]);

        if (! empty($type_by_plugin) && $existing_type !== $type_by_plugin) {
            $this->warnOverrideOfExistingType(
                $artifactlinkinfo,
                $from_artifact->getTracker(),
                $existing_type,
                $type_by_plugin
            );
        }
        return $type_by_plugin;
    }

    private function warnOverrideOfExistingType(
        Tracker_ArtifactLinkInfo $artifactlinkinfo,
        Tracker $from_tracker,
        $existing_type,
        $type_by_plugin,
    ) {
        if ($from_tracker->isProjectAllowedToUseType()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(dgettext('tuleap-tracker', 'Override link type "%2$s" to artifact #%1$s with type "%3$s" returned by another service'), $artifactlinkinfo->getArtifactId(), $existing_type, $type_by_plugin)
            );
        }
    }

    /**
     * Update cross references of this field
     *
     * @param Artifact $artifact        the artifact that is currently updated
     * @param array    $submitted_value the array of added and removed artifact links ($values['added_values'] is a string and $values['removed_values'] is an array of artifact ids
     *
     * @return bool
     */
    private function updateCrossReferences(PFUser $user, Artifact $artifact, array $submitted_value)
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

    private function canLinkArtifacts(Artifact $src_artifact, Artifact $artifact_to_link)
    {
        return ($src_artifact->getId() != $artifact_to_link->getId()) && $artifact_to_link->getTracker();
    }

    private function getAddedArtifactIds(array $values)
    {
        $ids = [];
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
        return [];
    }

    private function insertCrossReference(PFUser $user, Artifact $source_artifact, $target_artifact_id)
    {
        return $this->reference_manager->insertBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

    private function removeCrossReference(PFUser $user, Artifact $source_artifact, $target_artifact_id)
    {
        return $this->reference_manager->removeBetweenTwoArtifacts(
            $source_artifact,
            $this->artifact_factory->getArtifactById($target_artifact_id),
            $user
        );
    }

    /**
     * @return array<int, array<string, int[]>>
     */
    private function getArtifactIdsToLink(
        Tracker $from_tracker,
        Artifact $artifact,
        array $submitted_value,
    ): array {
        $all_artifact_to_be_linked = [];
        foreach ($submitted_value['list_of_artifactlinkinfo'] as $artifactlinkinfo) {
            $artifact_to_link = $artifactlinkinfo->getArtifact();
            if ($this->canLinkArtifacts($artifact, $artifact_to_link)) {
                $tracker = $artifact_to_link->getTracker();
                $type    = $this->getType($artifact, $artifactlinkinfo, $from_tracker, $tracker, $submitted_value);

                if (! isset($all_artifact_to_be_linked[$tracker->getId()])) {
                    $all_artifact_to_be_linked[$tracker->getId()] = [];
                }

                if (! isset($all_artifact_to_be_linked[$tracker->getId()][$type])) {
                    $all_artifact_to_be_linked[$tracker->getId()][$type] = [];
                }

                $all_artifact_to_be_linked[$tracker->getId()][$type][] = $artifact_to_link->getId();
            }
        }

        return $all_artifact_to_be_linked;
    }
}
