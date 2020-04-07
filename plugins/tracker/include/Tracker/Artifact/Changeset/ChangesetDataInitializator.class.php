<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tracker_Artifact_Changeset_ChangesetDataInitializator
{

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory)
    {
        $this->formelement_factory = $formelement_factory;
    }

    public function process(Tracker_Artifact $artifact, array $fields_data)
    {
        $tracker_data = array();

        //only when a previous changeset exists
        if (! $artifact->getLastChangeset() instanceof Tracker_Artifact_Changeset_Null) {
            foreach ($artifact->getLastChangeset()->getValues() as $key => $field) {
                if ($field instanceof Tracker_Artifact_ChangesetValue_Date || $field instanceof Tracker_Artifact_ChangesetValue_List) {
                    $tracker_data[$key] = $field->getValue();
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
                 $tracker_data[$field->getId()] = date("Y-m-d");
            }
            if ($field instanceof Tracker_FormElement_Field_SubmittedOn) {
                 $tracker_data[$field->getId()] = $artifact->getSubmittedOn();
            }
            if (
                $field instanceof Tracker_FormElement_Field_Date &&
                    ! array_key_exists($field->getId(), $tracker_data)
            ) {
                //user doesn't have access to field
                $tracker_data[$field->getId()] = $field->getValue($field->getId());
            }
        }

        return $tracker_data;
    }
}
