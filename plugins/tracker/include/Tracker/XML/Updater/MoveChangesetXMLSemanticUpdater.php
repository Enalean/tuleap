<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Tracker\XML\Updater;

use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveContributorSemanticChecker;
use Tuleap\Tracker\Action\MoveDescriptionSemanticChecker;
use Tuleap\Tracker\Action\MoveStatusSemanticChecker;
use Tuleap\Tracker\Action\MoveTitleSemanticChecker;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
use Tuleap\Tracker\Events\MoveArtifactParseFieldChangeNodes;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingUserValue;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class MoveChangesetXMLSemanticUpdater
{
    public function __construct(
        private readonly MoveChangesetXMLUpdater $move_changeset_XML_updater,
        private readonly MoveTitleSemanticChecker $title_semantic_checker,
        private readonly MoveDescriptionSemanticChecker $description_semantic_checker,
        private readonly MoveStatusSemanticChecker $status_semantic_checker,
        private readonly MoveContributorSemanticChecker $contributor_semantic_checker,
        private readonly \EventManager $event_manager,
        private readonly UpdateBindValueForSemantic $update_bind_value,
        private readonly RetrieveMatchingUserValue $field_value_matcher,
    ) {
    }

    public function update(
        PFUser $current_user,
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        int $submitted_on,
        int $moved_time,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $artifact_xml['tracker_id'] = $target_tracker->getId();

        $this->parseChangesetNodes(
            $source_tracker,
            $target_tracker,
            $artifact_xml,
            $submitted_by,
            $submitted_on,
            $feedback_field_collector
        );

        if (count($artifact_xml->changeset) > 0) {
            $this->move_changeset_XML_updater->addLastMovedChangesetComment(
                $current_user,
                $artifact_xml,
                $source_tracker,
                $moved_time
            );
        }
    }

    /**
     * Parse the SimpleXMLElement changeset nodes to prepare the move action.
     *
     * The parse is done in reverse order to be able to delete a SimpleXMLElement without any issues.
     */
    private function parseChangesetNodes(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        int $submitted_on,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $last_index = $artifact_xml->changeset === null ? -1 : count($artifact_xml->changeset) - 1;
        if ($artifact_xml->changeset === null) {
            return;
        }
        for ($index = $last_index; $index >= 0; $index--) {
            $this->parseFieldChangeNodesInReverseOrder(
                $source_tracker,
                $target_tracker,
                $artifact_xml->changeset[$index],
                $feedback_field_collector
            );

            if ($this->move_changeset_XML_updater->isChangesetNodeDeletable($artifact_xml, $index)) {
                $this->move_changeset_XML_updater->deleteChangesetNode($artifact_xml, $index);
            }

            if ($index === 0) {
                $this->move_changeset_XML_updater->addSubmittedInformation($artifact_xml->changeset[$index], $submitted_by, $submitted_on);
            }
        }
    }

    /**
     * Parse the SimpleXMLElement field_change nodes to prepare the move action.
     *
     * The parse is done in reverse order to be able to delete a SimpleXMLElement without any issues.
     */
    private function parseFieldChangeNodesInReverseOrder(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $title_semantic_can_be_moved = $this->title_semantic_checker->areSemanticsAligned(
            $source_tracker,
            $target_tracker
        );

        $description_semantic_can_be_moved = $this->description_semantic_checker->areSemanticsAligned(
            $source_tracker,
            $target_tracker
        );

        $status_semantic_can_be_moved = $this->status_semantic_checker->areSemanticsAligned(
            $source_tracker,
            $target_tracker
        );

        $contributor_semantic_can_be_moved = $this->contributor_semantic_checker->areSemanticsAligned(
            $source_tracker,
            $target_tracker
        );

        $external_semantic_can_be_moved = $this->canExternalSemanticBeMoved($source_tracker, $target_tracker);

        $target_title_field       = $target_tracker->getTitleField();
        $target_description_field = $target_tracker->getDescriptionField();
        $target_status_field      = $target_tracker->getStatusField();
        $target_contributor_field = $target_tracker->getContributorField();

        $source_status_field      = $source_tracker->getStatusField();
        $source_contributor_field = $source_tracker->getContributorField();

        $this->move_changeset_XML_updater->deleteEmptyCommentsNode($changeset_xml);

        $last_index = $changeset_xml->field_change === null ? -1 : count($changeset_xml->field_change) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            $modified = false;
            if (
                $title_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToTitleSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->move_changeset_XML_updater->useTargetTrackerFieldName($changeset_xml, $target_title_field, $index);
                continue;
            } elseif (
                $description_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToDescriptionSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->move_changeset_XML_updater->useTargetTrackerFieldName($changeset_xml, $target_description_field, $index);
                continue;
            } elseif (
                $status_semantic_can_be_moved &&
                $target_status_field !== null &&
                $this->isFieldChangeCorrespondingToStatusSemanticField($changeset_xml, $source_status_field, $index)
            ) {
                $this->move_changeset_XML_updater->useTargetTrackerFieldName($changeset_xml, $target_status_field, $index);
                $this->update_bind_value->updateValueForSemanticMove($changeset_xml, $source_status_field, $target_status_field, $index, $feedback_field_collector);
                continue;
            } elseif (
                $contributor_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToContributorSemanticField($changeset_xml, $source_contributor_field, $index)
            ) {
                $this->move_changeset_XML_updater->useTargetTrackerFieldName($changeset_xml, $target_contributor_field, $index);
                $this->removeNonPossibleUserValues(
                    $source_contributor_field,
                    $changeset_xml,
                    $target_contributor_field,
                    $index,
                    $feedback_field_collector
                );
                continue;
            } elseif ($external_semantic_can_be_moved) {
                $modified = $this->parseFieldChangeNodesForExternalSemantics(
                    $source_tracker,
                    $target_tracker,
                    $changeset_xml,
                    $index,
                    $feedback_field_collector
                );
            }

            if ($modified === false) {
                $this->move_changeset_XML_updater->deleteFieldChangeNode($changeset_xml, $index);
            }
        }
    }

    private function canExternalSemanticBeMoved(Tracker $source_tracker, Tracker $target_tracker): bool
    {
        $event = new MoveArtifactGetExternalSemanticCheckers();
        $this->event_manager->processEvent($event);

        foreach ($event->getExternalSemanticsCheckers() as $external_semantics_checker) {
            if ($external_semantics_checker->areSemanticsAligned($source_tracker, $target_tracker)) {
                return true;
            }
        }

        return false;
    }

    private function parseFieldChangeNodesForExternalSemantics(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        int $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): bool {
        $event = new MoveArtifactParseFieldChangeNodes(
            $source_tracker,
            $target_tracker,
            $changeset_xml,
            $feedback_field_collector,
            $index
        );

        $this->event_manager->processEvent($event);

        return $event->isModifiedByPlugin();
    }

    private function isFieldChangeCorrespondingToTitleSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker $source_tracker,
        int $index,
    ): bool {
        $source_title_field = $source_tracker->getTitleField();
        if ($source_title_field && $this->move_changeset_XML_updater->isFieldChangeCorrespondingToField($changeset_xml, $source_title_field, $index)) {
            return true;
        }

        return false;
    }

    private function isFieldChangeCorrespondingToDescriptionSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker $source_tracker,
        int $index,
    ): bool {
        $source_description_field = $source_tracker->getDescriptionField();
        if (
            $source_description_field &&
            $this->move_changeset_XML_updater->isFieldChangeCorrespondingToField($changeset_xml, $source_description_field, $index)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @psalm-assert-if-true !null $source_status_field
     */
    private function isFieldChangeCorrespondingToStatusSemanticField(
        SimpleXMLElement $changeset_xml,
        ?Tracker_FormElement_Field $source_status_field,
        int $index,
    ): bool {
        return $source_status_field !== null && $this->move_changeset_XML_updater->isFieldChangeCorrespondingToField($changeset_xml, $source_status_field, $index);
    }

    private function isFieldChangeCorrespondingToContributorSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_contributor_field,
        int $index,
    ): bool {
        return $this->move_changeset_XML_updater->isFieldChangeCorrespondingToField($changeset_xml, $source_contributor_field, $index);
    }

    private function removeNonPossibleUserValues(
        Tracker_FormElement_Field $source_contributor_field,
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field_List $target_contributor_field,
        int $field_change_index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $last_index = count($changeset_xml->field_change[$field_change_index]->value) - 1;
        for ($value_index = $last_index; $value_index >= 0; $value_index--) {
            if (
                ! $this->field_value_matcher->isSourceUserValueMatchingADestinationUserValue(
                    $target_contributor_field,
                    $changeset_xml->field_change[$field_change_index]->value[$value_index]
                )
            ) {
                unset($changeset_xml->field_change[$field_change_index]->value[$value_index]);

                $feedback_field_collector->addFieldInPartiallyMigrated($source_contributor_field);
            }
        }
    }
}
