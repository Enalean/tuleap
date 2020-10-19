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

declare(strict_types=1);

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ChangesetInstrumentation;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;

/**
 * I am a Template Method to create an initial changeset.
 */
abstract class Tracker_Artifact_Changeset_ChangesetCreatorBase
{
    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    protected $fields_validator;

    /** @var Tracker_ArtifactFactory */
    protected $artifact_factory;

    /** @var Tracker_Artifact_Changeset_ChangesetDataInitializator */
    protected $field_initializator;

    /** @var EventManager */
    protected $event_manager;
    /**
     * @var FieldsToBeSavedInSpecificOrderRetriever
     */
    protected $fields_retriever;

    public function __construct(
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        FieldsToBeSavedInSpecificOrderRetriever $fields_retriever,
        Tracker_ArtifactFactory $artifact_factory,
        EventManager $event_manager,
        Tracker_Artifact_Changeset_ChangesetDataInitializator $field_initializator
    ) {
        $this->fields_validator    = $fields_validator;
        $this->artifact_factory    = $artifact_factory;
        $this->event_manager       = $event_manager;
        $this->field_initializator = $field_initializator;
        $this->fields_retriever    = $fields_retriever;
    }

    protected function isFieldSubmitted(Tracker_FormElement_Field $field, array $fields_data): bool
    {
        return isset($fields_data[$field->getId()]);
    }

    /**
     * Should we move this method outside of changeset creation
     * so that we can remove the dependency on artifact factory
     * and enforce SRP ?
     */
    protected function saveArtifactAfterNewChangeset(
        Artifact $artifact,
        array $fields_data,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ): bool {
        if ($this->artifact_factory->save($artifact)) {
            foreach ($this->fields_retriever->getFields($artifact) as $field) {
                $field->postSaveNewChangeset($artifact, $submitter, $new_changeset, $previous_changeset);
            }

            $artifact->getWorkflow()->after($fields_data, $new_changeset, $previous_changeset);

            ChangesetInstrumentation::increment();
            return true;
        }

        return false;
    }
}
