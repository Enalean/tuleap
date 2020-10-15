<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use PFUser;
use Project;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
final class InformationNeededToRetrieveTestStatusOfACampaign
{
    /**
     * @var int
     */
    public $campaign_id;
    /**
     * @var array<string|int>
     * @psalm-var non-empty-array<string|int>
     */
    public $current_user_ugroup_ids;
    /**
     * @var int
     */
    public $test_exec_status_field_id;
    /**
     * @var int
     */
    public $test_campaign_art_link_field_id;

    /**
     * @param array<string|int> $current_user_ugroup_ids
     * @psalm-param non-empty-array<string|int> $current_user_ugroup_ids
     */
    private function __construct(
        Artifact $campaign,
        array $current_user_ugroup_ids,
        int $test_exec_status_field_id,
        int $test_campaign_art_link_field_id
    ) {
        $this->campaign_id                     = (int) $campaign->getId();
        $this->current_user_ugroup_ids         = $current_user_ugroup_ids;
        $this->test_exec_status_field_id       = $test_exec_status_field_id;
        $this->test_campaign_art_link_field_id = $test_campaign_art_link_field_id;
    }

    public static function fromCampaign(
        Artifact $campaign,
        PFUser $user,
        Config $testmanagement_config,
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $form_element_factory
    ): ?self {
        if (! $campaign->userCanView($user)) {
            return null;
        }

        $test_campaign_art_link_field = self::getReadableByTheUserTestCampaignArtLinkFieldID($form_element_factory, $campaign, $user);
        if ($test_campaign_art_link_field === null) {
            return null;
        }

        $project = $campaign->getTracker()->getProject();

        $test_exec_status_field = self::getReadableByTheUserTestExecutionStatusFieldID($testmanagement_config, $tracker_factory, $project, $user);
        if ($test_exec_status_field === null) {
            return null;
        }

        return new self(
            $campaign,
            $user->getUgroups($project->getID(), []),
            $test_exec_status_field,
            $test_campaign_art_link_field,
        );
    }

    private static function getReadableByTheUserTestExecutionStatusFieldID(
        Config $testmanagement_config,
        TrackerFactory $tracker_factory,
        Project $project,
        PFUser $user
    ): ?int {
        $test_exec_tracker_id = $testmanagement_config->getTestExecutionTrackerId($project);

        if ($test_exec_tracker_id === false) {
            return null;
        }

        $test_exec_tracker = $tracker_factory->getTrackerById($test_exec_tracker_id);
        if ($test_exec_tracker === null || ! $test_exec_tracker->userCanView($user)) {
            return null;
        }

        $status_field = $test_exec_tracker->getStatusField();
        if ($status_field === null || ! $status_field->userCanRead($user)) {
            return null;
        }

        return (int) $status_field->getId();
    }

    private static function getReadableByTheUserTestCampaignArtLinkFieldID(
        Tracker_FormElementFactory $form_element_factory,
        Artifact $campaign,
        PFUser $user
    ): ?int {
        $test_campaign_tracker = $campaign->getTracker();

        $art_link_field = $form_element_factory->getAnArtifactLinkField($user, $test_campaign_tracker);

        if ($art_link_field === null) {
            return null;
        }

        return (int) $art_link_field->getId();
    }
}
