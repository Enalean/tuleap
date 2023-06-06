<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\Action\MoveContributorSemanticChecker;
use Tuleap\Tracker\Action\MoveDescriptionSemanticChecker;
use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\MoveStatusSemanticChecker;
use Tuleap\Tracker\Action\MoveTitleSemanticChecker;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
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

    /**
     * @var MoveContributorSemanticChecker
     */
    private $contributor_semantic_checker;

    public function __construct(
        EventManager $event_manager,
        FieldValueMatcher $field_value_matcher,
        MoveTitleSemanticChecker $title_semantic_checker,
        MoveDescriptionSemanticChecker $description_semantic_checker,
        MoveStatusSemanticChecker $status_semantic_checker,
        MoveContributorSemanticChecker $contributor_semantic_checker,
    ) {
        $this->event_manager                = $event_manager;
        $this->field_value_matcher          = $field_value_matcher;
        $this->status_semantic_checker      = $status_semantic_checker;
        $this->title_semantic_checker       = $title_semantic_checker;
        $this->description_semantic_checker = $description_semantic_checker;
        $this->contributor_semantic_checker = $contributor_semantic_checker;
    }

    public function update(
        PFUser $current_user,
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        $submitted_on,
        $moved_time,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
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
            $this->addLastMovedChangesetComment(
                $current_user,
                $artifact_xml,
                $source_tracker,
                $moved_time
            );
        }
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
        $submitted_on,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
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
        SimpleXMLElement $changeset_xml,
        FeedbackFieldCollectorInterface $feedback_field_collector,
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

        $this->deleteEmptyCommentsNode($changeset_xml);

        $last_index = $changeset_xml->field_change === null ? -1 : count($changeset_xml->field_change) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            $modified = false;
            if (
                $title_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToTitleSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_title_field, $index);
                continue;
            } elseif (
                $description_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToDescriptionSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_description_field, $index);
                continue;
            } elseif (
                $status_semantic_can_be_moved &&
                $target_status_field !== null &&
                $this->isFieldChangeCorrespondingToStatusSemanticField($changeset_xml, $source_status_field, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_status_field, $index);
                $this->updateValue($changeset_xml, $source_status_field, $target_status_field, $index, $feedback_field_collector);
                continue;
            } elseif (
                $contributor_semantic_can_be_moved &&
                $this->isFieldChangeCorrespondingToContributorSemanticField($changeset_xml, $source_contributor_field, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_contributor_field, $index);
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

        if ($changeset_xml->comments->comment === null || count($changeset_xml->comments->comment) === 0) {
            unset($changeset_xml->comments);
        }
    }

    private function deleteEmptyCommentNodes(SimpleXMLElement $comments_xml)
    {
        $last_index = $comments_xml->comment === null ? -1 : count($comments_xml->comment) - 1;
        if ($comments_xml->comment === null) {
            return;
        }
        for ($index = $last_index; $index >= 0; $index--) {
            if ((string) $comments_xml->comment[$index]->body === '') {
                unset($comments_xml->comment[$index]);
            }
        }
    }

    private function isFieldChangeCorrespondingToTitleSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker $source_tracker,
        $index,
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
        $index,
    ) {
        $source_description_field = $source_tracker->getDescriptionField();
        if (
            $source_description_field &&
            $this->isFieldChangeCorrespondingToField($changeset_xml, $source_description_field, $index)
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
        $index,
    ) {
        return $source_status_field !== null && $this->isFieldChangeCorrespondingToField($changeset_xml, $source_status_field, $index);
    }

    private function isFieldChangeCorrespondingToContributorSemanticField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_contributor_field,
        $index,
    ) {
        return $this->isFieldChangeCorrespondingToField($changeset_xml, $source_contributor_field, $index);
    }

    private function isFieldChangeCorrespondingToField(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_field,
        $index,
    ) {
        $field_change = $changeset_xml->field_change[$index];

        return (string) $field_change['field_name'] === $source_field->getName();
    }

    private function useTargetTrackerFieldName(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $target_field,
        $index,
    ) {
        $changeset_xml->field_change[$index]['field_name'] = $target_field->getName();
    }

    private function updateValue(
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field $source_status_field,
        Tracker_FormElement_Field $target_status_field,
        $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
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

        if ($value === null) {
            $value = $target_status_field->getDefaultValue();
            $feedback_field_collector->addFieldInPartiallyMigrated($source_status_field);
        }

        $changeset_xml->field_change[$index]->value = (int) $value;
    }

    private function removeNonPossibleUserValues(
        Tracker_FormElement_Field $source_contributor_field,
        SimpleXMLElement $changeset_xml,
        Tracker_FormElement_Field_List $target_contributor_field,
        $field_change_index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
        $last_index = count($changeset_xml->field_change[$field_change_index]->value) - 1;
        for ($value_index = $last_index; $value_index >= 0; $value_index--) {
            if (
                ! $this->field_value_matcher->isSourceUserValueMathingATargetUserValue(
                    $target_contributor_field,
                    $changeset_xml->field_change[$field_change_index]->value[$value_index]
                )
            ) {
                unset($changeset_xml->field_change[$field_change_index]->value[$value_index]);

                $feedback_field_collector->addFieldInPartiallyMigrated($source_contributor_field);
            }
        }
    }

    /**
     * @return bool
     */
    private function canExternalSemanticBeMoved(Tracker $source_tracker, Tracker $target_tracker)
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

    /**
     * @return bool
     */
    private function parseFieldChangeNodesForExternalSemantics(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
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

    private function addLastMovedChangesetComment(
        PFUser $current_user,
        SimpleXMLElement $artifact_xml,
        Tracker $source_tracker,
        $moved_time,
    ) {
        $last_changeset = $artifact_xml->addChild('changeset');

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($last_changeset, 'submitted_by', $current_user->getId(), ['format' => 'id']);
        $cdata->insertWithAttributes(
            $last_changeset,
            'submitted_on',
            date('c', $moved_time),
            ['format' => 'ISO8601']
        );

        $this->addLastChangesetCommentContent($current_user, $last_changeset, $source_tracker, $moved_time);
    }

    private function addLastChangesetCommentContent(
        PFUser $current_user,
        SimpleXMLElement $last_changeset,
        Tracker $source_tracker,
        int $moved_time,
    ) {
        $comments_tag = $last_changeset->addChild('comments');
        $comment_tag  = $comments_tag->addChild('comment');

        $cdata = new \XML_SimpleXMLCDATAFactory();
        $cdata->insertWithAttributes($comment_tag, 'submitted_by', $current_user->getId(), ['format' => 'id']);
        $cdata->insertWithAttributes(
            $comment_tag,
            'submitted_on',
            date('c', $moved_time),
            ['format' => 'ISO8601']
        );
        $cdata->insertWithAttributes(
            $comment_tag,
            'body',
            sprintf(
                dgettext('tuleap-tracker', "Artifact was moved from '%s' tracker in '%s' project."),
                $source_tracker->getName(),
                $source_tracker->getProject()->getPublicName()
            ),
            ['format' => 'text']
        );
    }

    public function updateFromDuckTypingCollection(
        PFUser $current_user,
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        int $submitted_on,
        int $moved_time,
        DuckTypedMoveFieldCollection $field_collection,
        Tracker $source_tracker,
    ): void {
        $this->parseChangesetNodesFromDuckTypingCollection(
            $artifact_xml,
            $submitted_by,
            $submitted_on,
            $field_collection
        );

        if (count($artifact_xml->changeset) > 0) {
            $this->addLastMovedChangesetComment(
                $current_user,
                $artifact_xml,
                $source_tracker,
                $moved_time
            );
        }
    }

    private function parseChangesetNodesFromDuckTypingCollection(
        SimpleXMLElement $artifact_xml,
        PFUser $submitted_by,
        int $submitted_on,
        DuckTypedMoveFieldCollection $feedback_field_collector,
    ): void {
        $last_index = $artifact_xml->changeset === null ? -1 : count($artifact_xml->changeset) - 1;
        if ($artifact_xml->changeset === null) {
            return;
        }
        for ($index = $last_index; $index >= 0; $index--) {
            $this->parseFieldChangeNodesInReverseOrderForDuckTypingCollection(
                $artifact_xml->changeset[$index],
                $feedback_field_collector
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
     * Parse the SimpleXMLElement field_change nodes to prepare the move action.
     *
     * The parse is done in reverse order to be able to delete a SimpleXMLElement without any issues.
     */
    private function parseFieldChangeNodesInReverseOrderForDuckTypingCollection(
        SimpleXMLElement $changeset_xml,
        DuckTypedMoveFieldCollection $feedback_field_collector,
    ): void {
        $this->deleteEmptyCommentsNode($changeset_xml);

        $last_index = $changeset_xml->field_change === null ? -1 : count($changeset_xml->field_change) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            if (! $changeset_xml->field_change || ! $changeset_xml->field_change[$index]) {
                continue;
            }
            $field_change = $changeset_xml->field_change[$index];
            if (empty((string) $field_change)) {
                continue;
            }

            foreach ($feedback_field_collector->not_migrateable_field_list as $not_migrateable) {
                if ($not_migrateable->getName() === (string) $field_change['field_name']) {
                    $this->deleteFieldChangeNode($changeset_xml, $index);
                }
            }

            foreach ($feedback_field_collector->migrateable_field_list as $migrateable) {
                $target_field = null;
                if ($migrateable->getName() === (string) $field_change['field_name']) {
                    foreach ($feedback_field_collector->mapping_fields as $mapping_field) {
                        if ($mapping_field->source === $migrateable) {
                            $target_field = $mapping_field->destination;
                        }
                    }

                    if ($target_field) {
                        $this->useTargetTrackerFieldName($changeset_xml, $target_field, $index);
                    }
                }
            }
        }
    }
}
