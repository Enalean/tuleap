<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use AgileDashboard_Semantic_InitialEffortFactory;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;

class MoveChangesetXMLUpdater
{
    public function __construct(
        private readonly AgileDashboard_Semantic_InitialEffortFactory $initial_effort_factory,
        private readonly Tracker_FormElementFactory $tracker_form_element_factory,
        private readonly RetrieveMatchingValueByDuckTyping $field_value_matcher,
    ) {
    }

    /**
     * @return bool
     */
    public function parseFieldChangeNodesAtGivenIndex(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml,
        $index,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
        $source_initial_effort_field = $this->initial_effort_factory->getByTracker($source_tracker)->getField();
        $target_initial_effort_field = $this->initial_effort_factory->getByTracker($target_tracker)->getField();
        $field_change                = $changeset_xml->field_change[$index];

        if (
            $source_initial_effort_field &&
            $target_initial_effort_field &&
            $this->isFieldChangeCorrespondingToTitleSemanticField($field_change, $source_initial_effort_field)
        ) {
            $this->updateFieldChangeNode(
                $field_change,
                $source_initial_effort_field,
                $target_initial_effort_field,
                $feedback_field_collector
            );
            return true;
        }

        return false;
    }

    private function isFieldChangeCorrespondingToTitleSemanticField(
        SimpleXMLElement $field_change,
        Tracker_FormElement_Field $source_field,
    ) {
        return (string) $field_change['field_name'] === $source_field->getName();
    }

    private function updateFieldChangeNode(
        SimpleXMLElement $field_change,
        Tracker_FormElement_Field $source_initial_effort_field,
        Tracker_FormElement_Field $target_initial_effort_field,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ) {
        $this->useTargetTrackerFieldName($field_change, $target_initial_effort_field);

        if ($this->areFieldsLists($source_initial_effort_field, $target_initial_effort_field)) {
            $this->updateValue(
                $field_change,
                $source_initial_effort_field,
                $target_initial_effort_field,
                $feedback_field_collector
            );
        }
    }

    private function useTargetTrackerFieldName(
        SimpleXMLElement $field_change,
        Tracker_FormElement_Field $target_field,
    ) {
        $field_change['field_name'] = $target_field->getName();
    }

    private function updateValue(
        SimpleXMLElement $field_change,
        Tracker_FormElement_Field $source_initial_effort_field,
        Tracker_FormElement_Field $target_initial_effort_field,
        FeedbackFieldCollectorInterface $feedback_field_collector,
    ): void {
        $xml_value = (int) $field_change->value;

        if ($xml_value === 0) {
            return;
        }

        assert($source_initial_effort_field instanceof \Tracker_FormElement_Field_List);
        assert($target_initial_effort_field instanceof \Tracker_FormElement_Field_List);

        $value = $this->field_value_matcher->getMatchingValueByDuckTyping(
            $source_initial_effort_field,
            $target_initial_effort_field,
            $xml_value
        );

        if ($value === null) {
            $value = $target_initial_effort_field->getDefaultValue();
            $feedback_field_collector->addFieldInPartiallyMigrated($source_initial_effort_field);
        }

        $field_change->value = (int) $value;
    }

    private function areFieldsLists(
        Tracker_FormElement_Field $source_initial_effort_field,
        Tracker_FormElement_Field $target_initial_effort_field,
    ) {
        return $this->tracker_form_element_factory->getType($source_initial_effort_field) ===
            $this->tracker_form_element_factory->getType($target_initial_effort_field) &&
            in_array($this->tracker_form_element_factory->getType($source_initial_effort_field), ['sb', 'rb']);
    }
}
