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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;

/**
 * I create a new changeset (update of an artifact) at a given date.
 *
 * This is used for import of history. This means that we cannot check
 * required fields or permissions as tracker structure has evolved between the
 * creation of the given artifact and now.
 */
class Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator extends Tracker_Artifact_Changeset_NewChangesetCreatorBase {

    public function __construct(
        Tracker_Artifact_Changeset_AtGivenDateFieldsValidator $fields_validator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_Artifact_ChangesetDao $changeset_dao,
        Tracker_Artifact_Changeset_CommentDao $changeset_comment_dao,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        ReferenceManager $reference_manager,
        SourceOfAssociationCollectionBuilder $source_of_association_collection_builder
    ) {
        parent::__construct(
            $fields_validator,
            $formelement_factory,
            $changeset_dao,
            $changeset_comment_dao,
            $artifact_factory,
            $event_manager,
            $reference_manager,
            $source_of_association_collection_builder
        );
    }

    /**
     * @see Tracker_Artifact_Changeset_NewChangesetCreatorBase::saveNewChangesetForField()
     */
    protected function saveNewChangesetForField(
        Tracker_FormElement_Field $field,
        Tracker_Artifact $artifact,
        $previous_changeset,
        array $fields_data,
        PFUser $submitter,
        $changeset_id
    ) {
        $is_submission = false;
        $bypass_perms  = true;

        if ($this->isFieldSubmitted($field, $fields_data)) {
            return $field->saveNewChangeset($artifact, $previous_changeset, $changeset_id, $fields_data[$field->getId()], $submitter, $is_submission, $bypass_perms);
        } else {
            return $field->saveNewChangeset($artifact, $previous_changeset, $changeset_id, null, $submitter, $is_submission, $bypass_perms);
        }
    }
}
