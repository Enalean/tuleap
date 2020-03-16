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

class Tracker_XML_Updater_ChangesetXMLUpdater
{

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
            $field_name = (string) $field_change['field_name'];
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

    private function addSubmittedInformation(SimpleXMLElement $changeset_xml, PFUser $user, $submitted_on)
    {
        $changeset_xml->submitted_on           = date('c', $submitted_on);
        $changeset_xml->submitted_by           = $user->getId();
        $changeset_xml->submitted_by['format'] = 'id';
    }
}
