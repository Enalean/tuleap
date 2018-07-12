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
 *Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * I create an initial changeset at a given date.
 *
 * This is used for import of history. This means that we cannot check
 * required fields or permissions as tracker structure has evolved between the
 * creation of the given artifact and now.
 */
class Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator extends Tracker_Artifact_Changeset_InitialChangesetCreatorBase {

    public function __construct(
        Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator,
        Tracker_FormElementFactory                            $formelement_factory,
        Tracker_Artifact_ChangesetDao                         $changeset_dao,
        Tracker_ArtifactFactory                               $artifact_factory,
        EventManager                                          $event_manager
    ) {
        parent::__construct(
            $fields_validator,
            $formelement_factory,
            $changeset_dao,
            $artifact_factory,
            $event_manager
        );
    }

    /**
     * @see parent::saveNewChangesetForField()
     */
    protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        $changeset_id
    ) {
        $is_submission = true;
        $bypass_perms  = true;
        if ($this->isFieldSubmitted($field, $fields_data)) {
            $field->saveNewChangeset($artifact, null, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
        }
    }
}