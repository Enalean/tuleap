<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

class Tracker_Artifact_Changeset_ChangesetDataInitializator
{
    public function __construct(private Tracker_FormElementFactory $formelement_factory)
    {
    }

    public function process(Artifact $artifact, array $fields_data): array
    {
        $tracker_data = [];

        //only when a previous changeset exists
        $previous_changeset = $artifact->getLastChangeset();
        if ($previous_changeset && ! $previous_changeset instanceof Tracker_Artifact_Changeset_Null) {
            foreach ($previous_changeset->getValues() as $key => $field) {
                if ($field instanceof Tracker_Artifact_ChangesetValue_Date) {
                    $tracker_data[$key] = $field->getTimestamp();
                }
                if ($field instanceof Tracker_Artifact_ChangesetValue_List) {
                    $field_value = $field->getValue();
                    if ($field_value) {
                        $tracker_data[$key] = $field_value;
                    } else {
                        $tracker_data[$key] = [Tracker_FormElement_Field_List::NONE_VALUE];
                    }
                }
            }
        }

        //replace where appropriate with submitted values
        foreach ($fields_data as $key => $value) {
            $tracker_data[$key] = $value;
        }

        //addlastUpdateDate and submitted on if available
        foreach ($this->formelement_factory->getAllFormElementsForTracker($artifact->getTracker()) as $field) {
            if ($field instanceof Tracker_FormElement_Field_LastUpdateDate) {
                 $tracker_data[$field->getId()] = date('Y-m-d');
            }
            if ($field instanceof Tracker_FormElement_Field_SubmittedOn) {
                 $tracker_data[$field->getId()] = $artifact->getSubmittedOn();
            }
            if (
                $field instanceof Tracker_FormElement_Field_Date &&
                    ! array_key_exists($field->getId(), $tracker_data)
            ) {
                //user doesn't have access to field
                $tracker_data[$field->getId()] = $field->getDefaultValue();
            }
            if ($field instanceof Tracker_FormElement_Field_List && ! isset($tracker_data[$field->getId()])) {
                $tracker_data[$field->getId()] = $field->getDefaultValue();
            }
        }

        return $tracker_data;
    }
}
