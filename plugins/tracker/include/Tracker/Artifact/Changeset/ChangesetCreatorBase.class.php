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

/**
 * I am a Template Method to create an initial changeset.
 */
abstract class Tracker_Artifact_Changeset_ChangesetCreatorBase {


    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    protected $fields_validator;

    /** @var Tracker_FormElementFactory */
    protected $formelement_factory;

    /** @var Tracker_ArtifactFactory */
    protected $artifact_factory;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_FormElementFactory $formelement_factory,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->fields_validator    = $fields_validator;
        $this->formelement_factory = $formelement_factory;
        $this->artifact_factory    = $artifact_factory;
    }

    /**
     * @return bool
     */
    protected function isFieldSubmitted(Tracker_FormElement_Field $field, array $fields_data) {
        return isset($fields_data[$field->getId()]);
    }

    /**
     * Used when validating the rules of a new/ initial changset creating.
     *
     * @param array $fields_data
     * @return array
     */
    protected function addDatesToRequestData(Tracker_Artifact $artifact, array $fields_data) {
        $tracker_data = array();

        //only when a previous changeset exists
        if(! $artifact->getLastChangeset() instanceof Tracker_Artifact_Changeset_Null) {
            foreach ($artifact->getLastChangeset()->getValues() as $key => $field) {
                if($field instanceof Tracker_Artifact_ChangesetValue_Date){
                    $tracker_data[$key] = $field->getValue();
                }
            }
        }

        //replace where appropriate with submitted values
        foreach ($fields_data as $key => $value) {
            $tracker_data[$key] = $value;
        }

        $elements = $this->formelement_factory->getAllFormElementsForTracker($artifact->getTracker());

        //addlastUpdateDate and submitted on if available
        foreach ($elements as $elm ) {
            if($elm instanceof Tracker_FormElement_Field_LastUpdateDate ) {
                 $tracker_data[$elm->getId()] = date("Y-m-d");
            }
            if($elm instanceof Tracker_FormElement_Field_SubmittedOn ) {
                 $tracker_data[$elm->getId()] = $artifact->getSubmittedOn();
            }

            if($elm instanceof Tracker_FormElement_Field_Date &&
                    ! array_key_exists($elm->getId(), $tracker_data)) {
                //user doesn't have access to field
                $tracker_data[$elm->getId()] = $elm->getValue($elm->getId());
            }
        }

        return $tracker_data;
    }

    /**
     * Should we move this method outside of changeset creation
     * so that we can remove the dependency on artifact factory
     * and enforce SRP ?
     */
    protected function saveArtifactAfterNewChangeset(
        Tracker_Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_Artifact_Changeset $previous_changeset = null
    ) {
        if ($this->artifact_factory->save($artifact)) {
            $used_fields = $this->formelement_factory->getUsedFields($artifact->getTracker());
            foreach ($used_fields as $field) {
                $field->postSaveNewChangeset($artifact, $submitter, $new_changeset, $previous_changeset);
            }

            $artifact->getWorkflow()->after($fields_data, $new_changeset, $previous_changeset);
        }
    }
}