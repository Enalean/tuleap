<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class Tracker_XML_Updater_ChangesetXMLUpdater {

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor
     */
    private $visitor;

    public function __construct(
        Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor $visitor,
        Tracker_FormElementFactory $formelement_factory
    ) {
        $this->visitor             = $visitor;
        $this->formelement_factory = $formelement_factory;
    }

    public function update(
        Tracker $tracker,
        SimpleXMLElement $artifact_xml,
        array $submitted_values,
        PFUser $user,
        $submitted_on
    ) {
        $this->addSubmittedInformation($artifact_xml->changeset, $user, $submitted_on);

        foreach ($artifact_xml->changeset->field_change as $field_change) {
            $field_name = (string)$field_change['field_name'];
            $field = $this->formelement_factory->getUsedFieldByNameForUser(
                $tracker->getId(),
                $field_name,
                $user
            );
            if ($field && isset($submitted_values[$field->getId()])) {
                $submitted_value = $submitted_values[$field->getId()];
                $this->visitor->update($field_change, $field, $submitted_value);
            }
        }
    }

    public function updateForMoveAction(
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

            if (count($artifact_xml->changeset[$index]->field_change) === 0 && $index > 0) {
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
    private function parseFieldChangeNodesInReverseOrder(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $changeset_xml
    ) {
        $target_title_field       = $target_tracker->getTitleField();
        $target_description_field = $target_tracker->getDescriptionField();

        $this->deleteCommentsNode($changeset_xml);

        $last_index = count($changeset_xml->field_change) - 1;
        for ($index = $last_index; $index >= 0; $index--) {
            if ($target_title_field &&
                $this->isFieldChangeCorrespondingToTitleSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_title_field, $index);
            } elseif ($target_description_field &&
                $this->isFieldChangeCorrespondingToDescriptionSemanticField($changeset_xml, $source_tracker, $index)
            ) {
                $this->useTargetTrackerFieldName($changeset_xml, $target_description_field, $index);
            } else {
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

    private function deleteCommentsNode(SimpleXMLElement $changeset_xml)
    {
        unset($changeset_xml->comments);
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
}
