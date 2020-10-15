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
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\InformationNeededToRetrieveTestStatusOfACampaign;
use Tuleap\TestManagement\Campaign\TestExecutionTestStatusDAO;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
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

    /** @var string ID of the artifact */
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

    public function __construct(
        string $id,
        string $label,
        string $status,
        int $nb_of_notrun,
        int $nb_of_passed,
        int $nb_of_failed,
        int $nb_of_blocked,
        array $resources,
        JobConfigurationRepresentation $job_configuration,
        bool $user_can_update
    ) {
        $this->id                = $id;
        $this->label             = $label;
        $this->status            = $status;
        $this->nb_of_notrun      = $nb_of_notrun;
        $this->nb_of_passed      = $nb_of_passed;
        $this->nb_of_failed      = $nb_of_failed;
        $this->nb_of_blocked     = $nb_of_blocked;
        $this->resources         = $resources;
        $this->job_configuration = $job_configuration;
        $this->user_can_update   = $user_can_update;

        $this->uri   = self::ROUTE . '/' . $this->id;
        $this->total = $nb_of_notrun + $nb_of_passed + $nb_of_failed + $nb_of_blocked;
    }

    public static function build(
        Campaign $campaign,
        Config $testmanagement_config,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $form_element_factory,
        TestExecutionTestStatusDAO $test_execution_test_status_dao,
        PFUser $user
    ): self {
        $artifact     = $campaign->getArtifact();
        $id           = $artifact->getId();
        $tracker_id   = $artifact->getTrackerId();
        $label_field  = self::getLabelField($form_element_factory, $tracker_id, $user);
        $field_value  = self::getFieldValue($artifact, $label_field);

        $executions_status = self::getExecutionsStatus(
            $artifact,
            $user,
            $testmanagement_config,
            $tracker_factory,
            $form_element_factory,
            $test_execution_test_status_dao
        );

        $user_can_update   = self::isUserAllowedToUpdateLabelField($user, $artifact, $label_field);
        $job_configuration = new JobConfigurationRepresentation(
            $campaign->getJobConfiguration(),
            $user_can_update
        );

        return new self(
            (string) $id,
            $field_value instanceof \Tracker_Artifact_ChangesetValue_Text ? $field_value->getText() : '',
            $artifact->getStatus(),
            $executions_status[self::STATUS_NOT_RUN],
            $executions_status[self::STATUS_PASSED],
            $executions_status[self::STATUS_FAILED],
            $executions_status[self::STATUS_BLOCKED],
            [
                [
                    'type' => ExecutionRepresentation::ROUTE,
                    'uri'  => self::ROUTE . '/' . $id . '/' . ExecutionRepresentation::ROUTE
                ]
            ],
            $job_configuration,
            $user_can_update,
        );
    }

    private static function getFieldValue(Artifact $artifact, Tracker_FormElement_Field $field): ?\Tracker_Artifact_ChangesetValue
    {
        return $artifact->getValue($field);
    }

    /**
     * @return int[]
     *
     * @psalm-return array{notrun: int, blocked: int, passed: int, failed: int}
     */
    private static function getExecutionsStatus(
        \Tuleap\Tracker\Artifact\Artifact $campaign_artifact,
        \PFUser $user,
        Config $testmanagement_config,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $form_element_factory,
        TestExecutionTestStatusDAO $test_execution_test_status_dao
    ): array {
        $information = InformationNeededToRetrieveTestStatusOfACampaign::fromCampaign(
            $campaign_artifact,
            $user,
            $testmanagement_config,
            $tracker_factory,
            $form_element_factory
        );

        if ($information === null) {
            return [
                self::STATUS_NOT_RUN => 0,
                self::STATUS_BLOCKED => 0,
                self::STATUS_PASSED  => 0,
                self::STATUS_FAILED  => 0
            ];
        }

        return $test_execution_test_status_dao->searchTestStatusesInACampaign($information);
    }

    private static function isUserAllowedToUpdateLabelField(
        PFUser $user,
        Artifact $artifact,
        Tracker_FormElement_Field $label_field
    ): bool {
        return $artifact->userCanUpdate($user) && $label_field->userCanUpdate($user);
    }

    /**
     * @return Tracker_FormElement_Field
     */
    private static function getLabelField(Tracker_FormElementFactory $form_element_factory, int $tracker_id, PFUser $user)
    {
        return $form_element_factory->getUsedFieldByNameForUser(
            $tracker_id,
            self::FIELD_NAME,
            $user
        );
    }
}
