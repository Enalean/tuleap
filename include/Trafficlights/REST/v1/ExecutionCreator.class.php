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

namespace Tuleap\Trafficlights\REST\v1;

use Luracast\Restler\RestException;
use Tracker_FormElementFactory;
use ProjectManager;
use PFUser;
use Tuleap\Trafficlights\Config;
use TrackerFactory;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tracker_REST_Artifact_ArtifactCreator;

class ExecutionCreator {

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Config */
    private $config;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_REST_Artifact_ArtifactCreator */
    private $artifact_creator;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        Config $config,
        ProjectManager $project_manager,
        TrackerFactory $tracker_factory,
        Tracker_REST_Artifact_ArtifactCreator $artifact_creator
    ) {
        $this->formelement_factory = $formelement_factory;
        $this->config              = $config;
        $this->project_manager     = $project_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->artifact_creator    = $artifact_creator;
    }

    /**
     * @return ArtifactReference
     */
    public function createTestExecution($project_id, PFUser $user, $definition_id) {
        $tracker = $this->getExecutionTrackerReferenceForProject($project_id);
        $values  = $this->getFieldValuesForExecutionArtifactCreation($tracker, $user, $definition_id);

        return $this->artifact_creator->create($user, $tracker, $values);
    }

    private function getExecutionTrackerReferenceForProject($project_id) {
        $project = $this->project_manager->getProject($project_id);
        if ($project->isError()) {
            throw new RestException(404, 'Project not found');
        }

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($project);
        $execution_tracker    = $this->tracker_factory->getTrackerById($execution_tracker_id);
        if (! $execution_tracker) {
            throw new RestException(400, 'The project does not contain an execution tracker');
        }

        $tracker_reference = new TrackerReference();
        $tracker_reference->build($execution_tracker);

        return $tracker_reference;
    }

    private function getFieldValuesForExecutionArtifactCreation(
        TrackerReference $tracker_reference,
        PFUser $user,
        $definition_id
    ) {
        $status_field      = $this->getStatusField($tracker_reference, $user);
        $link_field        = $this->getArtifactLinksField($tracker_reference, $user);

        $status_value                 = new ArtifactValuesRepresentation();
        $status_value->field_id       = (int)$status_field->getId();
        $status_value->bind_value_ids = array($status_field->getDefaultValue());

        $link_value           = new ArtifactValuesRepresentation();
        $link_value->field_id = (int)$link_field->getId();
        $link_value->links    = array(array('id' => $definition_id));

        return array($status_value, $link_value);
    }

    /** @return Tracker_FormElement_Field_List */
    private function getStatusField(
        TrackerReference $tracker_reference,
        PFUser $user
    ) {
        return $this->getField(
            $tracker_reference,
            $user,
            ExecutionRepresentation::FIELD_STATUS
        );
    }

    /** @return Tracker_FormElement_Field_ArtifactLink */
    private function getArtifactLinksField(
        TrackerReference $tracker_reference,
        PFUser $user
    ) {
        return $this->getField(
            $tracker_reference,
            $user,
            ExecutionRepresentation::FIELD_ARTIFACT_LINKS
        );
    }

    private function getField(
        TrackerReference $tracker_reference,
        PFUser $user,
        $field_name
    ) {
        $field = $this->formelement_factory->getUsedFieldByNameForUser(
            $tracker_reference->id,
            $field_name,
            $user
        );
        if (! $field) {
            throw new RestException(400, "No $field_name field. Execution tracker misconfigured");
        }

        return $field;
    }
}
