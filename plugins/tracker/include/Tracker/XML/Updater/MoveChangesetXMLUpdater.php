<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\XML\Updater;

use EventManager;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Action\MoveDescriptionSemanticChecker;
use Tuleap\Tracker\Action\MoveStatusSemanticChecker;
use Tuleap\Tracker\Action\MoveTitleSemanticChecker;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticTargetField;
use Tuleap\Tracker\Events\MoveArtifactParseFieldChangeNodes;
use Tuleap\Tracker\FormElement\Field\ListFields\FieldValueMatcher;

class MoveChangesetXMLUpdater
{

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var FieldValueMatcher
     */
    private $field_value_matcher;

    /**
     * @var MoveStatusSemanticChecker
     */
    private $status_semantic_checker;

    /**
     * @var MoveTitleSemanticChecker
     */
    private $title_semantic_checker;

    /**
     * @var MoveDescriptionSemanticChecker
     */
    private $description_semantic_checker;

    public function __construct(
        EventManager $event_manager,
        FieldValueMatcher $field_value_matcher,
        MoveTitleSemanticChecker $title_semantic_checker,
        MoveDescriptionSemanticChecker $description_semantic_checker,
        MoveStatusSemanticChecker $status_semantic_checker
    ) {
        $this->event_manager           = $event_manager;
        $this->field_value_matcher     = $field_value_matcher;
        $this->status_semantic_checker = $status_semantic_checker;
        $this->title_semantic_checker  = $title_semantic_checker;
        $this->description_semantic_checker = $description_semantic_checker;
    }

    public function update(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        $submitted_on
    ) {
        $artifact_xml['tracker_id'] = $target_tracker->getId();

        $this->parseChangesetNodes($source_tracker, $target_tracker, $artifact_xml, $submitted_by, $submitted_on);
    }

    private function addSubmittedInformation(SimpleXMLElement $changeset_xml, PFUser $user, $submitted_on)
    {
        $changeset_xml->submitted_on           = date('c', $submitted_on);
        $changeset_xml->submitted_by           = $user->getId();
        $changeset_xml->submitted_by['format'] = 'id';
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
        $submitted_on
    ) {
        $last_index = count($artifact_xml->changeset) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            $this->parseFieldChangeNodesInReverseOrder(
                $source_tracker,
                $target_tracker,
                $artifact_xml->changeset[$index]
            );

            if ($this->isChangesetNodeDeletable($artifact_xml, $index)) {
                $this->deleteChangesetNode($artifact_xml, $index);
            }

            if ($index === 0) {
                $this->addSubmittedInformation($artifact_xml->changeset[$index], $submitted_by, $submitted_on);
            }
        }
    }

    /**
     * @return bool
     */
    private function isChangesetNodeDeletable(SimpleXMLElement $artifact_xml, $index)
    {
        return count($artifact_xml->changeset[$index]->field_change) === 0 &&
            count($artifact_xml->changeset[$index]->comments) === 0 &&
            $index > 0;
    }

    /**
     * Parse the SimpleXMLElement field_change nodes to prepare the move action.
     *
     * The parse is done in reverse order to be able to delete a SimpleXMLElement without any issues.
     */
    private function parseFieldChangeNodesInReverseOrder(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml
    ) {
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

        $target_title_field             = $target_tracker->getTitleField();
        $target_description_field       = $target_tracker->getDescriptionField();
        $target_status_field            = $target_tracker->getStatusField();
        $source_status_field            = $source_tracker->getStatusField();
        $external_semantic_target_field = $this->getExternalSemanticTargetField($source_tracker, $target_tracker);

        $this->deleteEmptyCommentsNode($changeset_xml);

        $last_index = count($changeset_xml->field_change) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            $modified = false;
            if ($title_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToTitleSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_title_field, $index);
                continue;
            } elseif ($description_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToDescriptionSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_description_field, $index);
                continue;
            } elseif ($status_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToStatusSemanticField($changeset_xml, $source_status_field, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_status_field, $index);
                $this->updateValue($changeset_xml, $source_status_field, $target_status_field, $index);
                continue;
            } elseif ($external_semantic_target_field) {
                $modified = $this->parseFieldChangeNodesForExternalSemantics(
                    $source_tracker,
                    $target_tracker,
                    $changeset_xml,
                    $index
                );
            }

            if ($modified === false) {
                $this->deleteFieldChangeNode($changeset_xml, $index);
            }
        }
    }

    private function deleteChangesetNode(SimpleXMLElement $artifact_xml, $index)
    {
        unset($artifact_xml->changeset[$index]);
    }

    private function deleteFieldChangeNode(SimpleXMLElement $changeset_xml, $index)
    {
        unset($changeset_xml->field_change[$index]);
    }

    private function deleteEmptyCommentsNode(SimpleXMLElement $changeset_xml)
    {
        $this->deleteEmptyCommentNodes($changeset_xml->comments);

        if (count($changeset_xml->comments->comment) === 0) {
            unset($changeset_xml->comments);
        }
    }

    private function deleteEmptyCommentNodes(SimpleXMLElement $comments_xml)
    {
        $last_index = count($comments_xml->comment) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            if ((string) $comments_xml->comment[$index]->body === '') {
                unset($comments_xml->comment[$index]);
            }
        }
    }

    private function isFieldChangeCorrespondingToTitleSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker $source_tracker,
        $index
    ) {
        $source_title_field = $source_tracker->getTitleField();
        if ($source_title_field && $this->isFieldChangeCorrespondingToField($changeset_xml, $source_title_field, $index)) {
            return true;
        }

        return false;
    }

    private function isFieldChangeCorrespondingToDescriptionSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker $source_tracker,
        $index
    ) {
        $source_description_field = $source_tracker->getDescriptionField();
        if ($source_description_field &&
            $this->isFieldChangeCorrespondingToField($changeset_xml, $source_description_field, $index)
        ) {
            return true;
        }

        return false;
    }

    private function isFieldChangeCorrespondingToStatusSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_status_field,
        $index
    ) {
        return $this->isFieldChangeCorrespondingToField($changeset_xml, $source_status_field, $index);
    }

    private function isFieldChangeCorrespondingToField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_field,
        $index
    ) {
        $field_change = $changeset_xml->field_change[$index];

        return (string)$field_change['field_name'] === $source_field->getName();
    }

    private function useTargetTrackerFieldName(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $target_field,
        $index
    ) {
        $changeset_xml->field_change[$index]['field_name'] = $target_field->getName();
    }

    private function updateValue(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_status_field,
        Tracker_FormElement_Field $target_status_field,
        $index
    ) {
        $xml_value = (int) $changeset_xml->field_change[$index]->value;

        if ($xml_value === 0) {
            return;
        }

        $value = $this->field_value_matcher->getMatchingValueByDuckTyping(
            $source_status_field,
            $target_status_field,
            $xml_value
        );

        $changeset_xml->field_change[$index]->value = (int) $value;
    }

    /**
     * @return null|Tracker_FormElement_Field
     */
    private function getExternalSemanticTargetField(Tracker $source_tracker, Tracker $target_tracker)
    {
        $event = new MoveArtifactGetExternalSemanticTargetField($source_tracker, $target_tracker);
        $this->event_manager->processEvent($event);

        return $event->getField();
    }

    /**
     * @return bool
     */
    private function parseFieldChangeNodesForExternalSemantics(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        $index
    ) {
        $event = new MoveArtifactParseFieldChangeNodes($source_tracker, $target_tracker, $changeset_xml, $index);
        $this->event_manager->processEvent($event);

        return $event->isModifiedByPlugin();
    }
}
