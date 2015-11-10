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
 * I create artifact from the request in a Tracker
 */
class Tracker_ArtifactCreator {

    /** @var Tracker_ArtifactDao */
    private $artifact_dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_Artifact_Changeset_InitialChangesetCreatorBase */
    private $changeset_creator;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_Artifact_Changeset_FieldsValidator $fields_validator,
        Tracker_Artifact_Changeset_InitialChangesetCreatorBase $changeset_creator
    ) {
        $this->artifact_dao      = $artifact_factory->getDao();
        $this->artifact_factory  = $artifact_factory;
        $this->fields_validator  = $fields_validator;
        $this->changeset_creator = $changeset_creator;
    }

    /**
     * Add an artefact in the tracker
     *
     * @return Tracker_Artifact or false if an error occured
     */
    public function create(
        Tracker $tracker,
        array $fields_data,
        PFUser $user,
        $submitted_on,
        $send_notification
    ) {
        $artifact = $this->getBareArtifact($tracker, $user, $submitted_on);

        if (! $this->fields_validator->validate($artifact, $fields_data)) {
            return;
        }

        $use_artifact_permissions = 0;
        $id = $this->artifact_dao->create($tracker->id, $user->getId(), $submitted_on ,$use_artifact_permissions);
        if (! $id) {
            return;
        }

        $artifact->setId($id);
        $changeset_id = $this->changeset_creator->create($artifact, $fields_data, $user, $submitted_on);
        if (! $changeset_id) {
            return;
        }

        $changeset = new Tracker_Artifact_Changeset(
            $changeset_id,
            $artifact,
            $artifact->getSubmittedBy(),
            $artifact->getSubmittedOn(),
            $user->getEmail()
        );

        if ($send_notification) {
            $changeset->notify();
        }

        return $artifact;
    }

    private function getBareArtifact(Tracker $tracker, PFUser $user, $submitted_on) {
        $artifact = $this->artifact_factory->getInstanceFromRow(
            array(
                'id'                       => 0,
                'tracker_id'               => $tracker->id,
                'submitted_by'             => $user->getId(),
                'submitted_on'             => $submitted_on,
                'use_artifact_permissions' => 0,
            )
        );

        $artifact->setTracker($tracker);
        return $artifact;
    }
}
