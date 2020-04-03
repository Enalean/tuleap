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

use PFUser;
use Tracker_Artifact;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Campaign;

class CampaignRepresentation
{
    public const ROUTE = 'testmanagement_campaigns';

    public const FIELD_NAME           = "label";
    public const FIELD_ARTIFACT_LINKS = "artifact_links";
    public const FIELD_STATUS         = "status";

    public const STATUS_NOT_RUN = "notrun";
    public const STATUS_PASSED  = "passed";
    public const STATUS_FAILED  = "failed";
    public const STATUS_BLOCKED = "blocked";
    /**
     * @var int
     */
    public $total;

    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    /** @var int */
    private $tracker_id;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var PFUser */
    private $user;

    /** @var int ID of the artifact */
    public $id;

    /** @var String Label of the campaign */
    public $label;

    /** @var String Status of the campaign */
    public $status;

    /** @var String */
    public $uri;

    /** @var int */
    public $nb_of_notrun;

    /** @var int */
    public $nb_of_passed;

    /** @var int */
    public $nb_of_failed;

    /** @var int */
    public $nb_of_blocked;

    /** @var array */
    public $resources;

    /** @var JobConfigurationRepresentation */
    public $job_configuration;

    /** @var bool */
    public $user_can_update;

    public function build(
        Campaign $campaign,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user
    ): void {
        $artifact = $campaign->getArtifact();

        $this->artifact             = $artifact;
        $this->tracker_id           = $artifact->getTrackerId();
        $this->form_element_factory = $form_element_factory;
        $this->user                 = $user;
        $this->id                   = $artifact->getId();
        $this->uri                  = self::ROUTE . '/' . $this->id;

        $label_field  = $this->getLabelField();
        $field_value  = $this->getFieldValue($label_field);
        $this->label  = $field_value instanceof \Tracker_Artifact_ChangesetValue_Text ? $field_value->getText() : '';
        $this->status = $this->artifact->getStatus();

        $executions_status = $this->getExecutionsStatus();

        $this->nb_of_notrun  = $executions_status[self::STATUS_NOT_RUN];
        $this->nb_of_passed  = $executions_status[self::STATUS_PASSED];
        $this->nb_of_failed  = $executions_status[self::STATUS_FAILED];
        $this->nb_of_blocked = $executions_status[self::STATUS_BLOCKED];
        $this->total         = $this->nb_of_notrun + $this->nb_of_passed + $this->nb_of_failed + $this->nb_of_blocked;

        $this->user_can_update = $this->isUserAllowedToUpdateLabelField($user, $artifact, $label_field);
        $this->job_configuration = new JobConfigurationRepresentation();
        $this->job_configuration->build(
            $campaign->getJobConfiguration(),
            $this->user_can_update
        );

        $this->resources = [
            [
                'type' => ExecutionRepresentation::ROUTE,
                'uri'  => self::ROUTE . '/' . $this->id . '/' . ExecutionRepresentation::ROUTE
            ]
        ];
    }

    private function getFieldValue(Tracker_FormElement_Field $field): ?\Tracker_Artifact_ChangesetValue
    {
        return $this->artifact->getValue($field);
    }

    /**
     * @return int[]
     *
     * @psalm-return array{notrun: int, blocked: int, passed: int, failed: int}
     */
    private function getExecutionsStatus(): array
    {
        $executions = [
            self::STATUS_NOT_RUN => 0,
            self::STATUS_BLOCKED => 0,
            self::STATUS_PASSED  => 0,
            self::STATUS_FAILED  => 0
        ];

        $linked_artifacts = $this->artifact->getLinkedArtifacts($this->user);

        foreach ($linked_artifacts as $artifact) {
            if (isset($executions[$artifact->getStatus()])) {
                $executions[$artifact->getStatus()]++;
            }
        }

        return $executions;
    }

    /**
     *
     * @return bool
     */
    private function isUserAllowedToUpdateLabelField(
        PFUser $user,
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $label_field
    ) {
        return $artifact->userCanUpdate($user) && $label_field->userCanUpdate($user);
    }

    /**
     * @return Tracker_FormElement_Field
     */
    private function getLabelField()
    {
        return $this->form_element_factory->getUsedFieldByNameForUser(
            $this->tracker_id,
            self::FIELD_NAME,
            $this->user
        );
    }
}
