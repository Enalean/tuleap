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
        $this->addSubmittedInformation($artifact_xml, $user, $submitted_on);

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
        PFUser $user,
        $submitted_on
    ) {
        $artifact_xml['tracker_id'] = $target_tracker->getId();

        $this->addSubmittedInformation($artifact_xml, $user, $submitted_on);

        $this->parseFieldChangeNodesInReverseOrder($source_tracker, $target_tracker, $artifact_xml);
    }

    private function addSubmittedInformation(SimpleXMLElement $artifact_xml, PFUser $user, $submitted_on)
    {
        $artifact_xml->changeset->submitted_on = date('c', $submitted_on);
        $artifact_xml->changeset->submitted_by = $user->getId();
    }

    private function parseFieldChangeNodesInReverseOrder(
        Tracker $source_tracker,
        Tracker $target_tracker,
        SimpleXMLElement $artifact_xml
    ) {
        $source_title_field = $source_tracker->getTitleField()->getName();
        $last_index         = count($artifact_xml->changeset->field_change) - 1;
        for ($i = $last_index; $i >= 0; $i--) {
            if ($this->isFieldChangeCorrespondingToTitleField($artifact_xml, $source_title_field, $i)) {
                $this->useTargetTrackerFieldName($artifact_xml, $target_tracker, $i);
            } else {
                $this->deleteFieldChangeNode($artifact_xml, $i);
            }
        }
    }

    private function deleteFieldChangeNode(SimpleXMLElement $artifact_xml, $index)
    {
        unset($artifact_xml->changeset->field_change[$index]);
    }

    private function isFieldChangeCorrespondingToTitleField(SimpleXMLElement $artifact_xml, $source_title_field, $index)
    {
        $field_change = $artifact_xml->changeset->field_change[$index];

        return (string)$field_change['field_name'] === $source_title_field;
    }

    private function useTargetTrackerFieldName(SimpleXMLElement $artifact_xml, Tracker $target_tracker, $index)
    {
        $artifact_xml->changeset->field_change[$index]['field_name'] = $target_tracker->getTitleField()->getName();
    }
}
