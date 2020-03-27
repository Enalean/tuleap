<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_FormElementFactory;
use Tracker_REST_Artifact_ArtifactCreator;
use TrackerFactory;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\REST\Artifact\ArtifactReference;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class CampaignCreator
{

    /** @var Config */
    private $config;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var DefinitionSelector */
    private $definition_selector;

    /** @var Tracker_REST_Artifact_ArtifactCreator */
    private $artifact_creator;

    /** @var ExecutionCreator */
    private $execution_creator;

    public function __construct(
        Config $config,
        ProjectManager $project_manager,
        Tracker_FormElementFactory $formelement_factory,
        TrackerFactory $tracker_factory,
        DefinitionSelector $definition_selector,
        Tracker_REST_Artifact_ArtifactCreator $artifact_creator,
        ExecutionCreator $execution_creator
    ) {
        $this->config              = $config;
        $this->project_manager     = $project_manager;
        $this->formelement_factory = $formelement_factory;
        $this->tracker_factory     = $tracker_factory;
        $this->definition_selector = $definition_selector;
        $this->artifact_creator    = $artifact_creator;
        $this->execution_creator   = $execution_creator;
    }

    /**
     * @return ArtifactReference
     */
    public function createCampaign(PFUser $user, int $project_id, string $label, string $test_selector, int $milestone_id, int $report_id)
    {
        try {
            $execution_ids = $this->createTestExecutionsForDefinitions(
                $project_id,
                $user,
                $test_selector,
                $milestone_id,
                $report_id
            );
            $tracker       = $this->getCampaignTrackerReferenceForProject($project_id);
            $values        = $this->getFieldValuesForCampaignArtifactCreation($tracker, $user, $label, $execution_ids);
            $artifact_ref  = $this->artifact_creator->create($user, $tracker, $values);

            if (! empty($milestone_id)) {
                $artifact_ref->getArtifact()->linkArtifact($milestone_id, $user);
            }

            return $artifact_ref;
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_FileNotFoundException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @return int[][]
     *
     * @psalm-return list<array{id: int}>
     */
    private function createTestExecutionsForDefinitions(
        int $project_id,
        PFUser $user,
        string $test_selector,
        int $milestone_id,
        int $report_id
    ): array {
        $execution_ids = [];
        $project       = $this->project_manager->getProject($project_id);
        $definitions   = $this->definition_selector->selectDefinitions(
            $user,
            $project,
            $test_selector,
            $milestone_id,
            $report_id
        );

        foreach ($definitions as $definition) {
            $execution = $this->execution_creator->createTestExecution(
                $project_id,
                $user,
                $definition
            );
            $execution_ids[] = array('id' => $execution->id);
        }

        return $execution_ids;
    }

    private function getCampaignTrackerReferenceForProject(int $project_id): TrackerReference
    {
        $project = $this->project_manager->getProject($project_id);
        if ($project->isError()) {
            throw new RestException(404, 'Project not found');
        }

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);
        if (! $campaign_tracker_id) {
            throw new RestException(400, 'The project does not contain a campaign tracker');
        }
        $campaign_tracker    = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if (! $campaign_tracker) {
            throw new RestException(400, 'The project does not contain a campaign tracker');
        }

        $tracker_reference = new TrackerReference();
        $tracker_reference->build($campaign_tracker);

        return $tracker_reference;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     *
     * @psalm-return array{0: ArtifactValuesRepresentation, 1: ArtifactValuesRepresentation, 2: ArtifactValuesRepresentation}
     */
    private function getFieldValuesForCampaignArtifactCreation(
        TrackerReference $tracker_reference,
        PFUser $user,
        string $label,
        array $execution_ids
    ): array {
        $label_field  = $this->getField($tracker_reference, $user, CampaignRepresentation::FIELD_NAME);
        $status_field = $this->getField($tracker_reference, $user, CampaignRepresentation::FIELD_STATUS);
        $link_field   = $this->getField($tracker_reference, $user, CampaignRepresentation::FIELD_ARTIFACT_LINKS);

        $label_value           = new ArtifactValuesRepresentation();
        $label_value->field_id = (int) $label_field->getId();
        $label_value->value    = $label;

        $status_value                 = new ArtifactValuesRepresentation();
        $status_value->field_id       = (int) $status_field->getId();
        $status_value->bind_value_ids = array((int) $status_field->getDefaultValue());

        $link_value           = new ArtifactValuesRepresentation();
        $link_value->field_id = (int) $link_field->getId();
        $link_value->links    = $execution_ids;

        return array($label_value, $status_value, $link_value);
    }

    private function getField(
        TrackerReference $tracker_reference,
        PFUser $user,
        string $field_name
    ): \Tracker_FormElement_Field {
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
