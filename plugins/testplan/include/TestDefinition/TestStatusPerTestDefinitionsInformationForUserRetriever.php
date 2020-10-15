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

namespace Tuleap\TestPlan\TestDefinition;

use Tuleap\TestManagement\Config;

class TestStatusPerTestDefinitionsInformationForUserRetriever
{
    /**
     * @var Config
     */
    private $testmanagement_config;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Config $testmanagement_config, \TrackerFactory $tracker_factory, \Tracker_FormElementFactory $form_element_factory)
    {
        $this->testmanagement_config = $testmanagement_config;
        $this->tracker_factory       = $tracker_factory;
        $this->form_element_factory  = $form_element_factory;
    }

    /**
     * @param \Tuleap\Tracker\Artifact\Artifact[] $test_definitions
     */
    public function getInformationNeededToRetrieveTestStatusPerTestDefinition(
        \PFUser $user,
        \Tuleap\Tracker\Artifact\Artifact $milestone,
        array $test_definitions
    ): ?TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition {
        if (empty($test_definitions)) {
            return null;
        }

        $project = $milestone->getTracker()->getProject();

        $test_exec_status_field_id = $this->getTestExecutionStatusFieldID($project, $user);
        if ($test_exec_status_field_id === null) {
            return null;
        }

        $test_exec_art_link_field_id = $this->getReadableByTheUserArtLinkFieldID(
            $this->testmanagement_config->getTestExecutionTrackerId($project),
            $user
        );
        $test_campaign_art_link_field_id = $this->getReadableByTheUserArtLinkFieldID(
            $this->testmanagement_config->getCampaignTrackerId($project),
            $user
        );
        if (
            $test_exec_art_link_field_id === null ||
            $test_campaign_art_link_field_id === null
        ) {
            return null;
        }

        return new TestPlanMilestoneInformationNeededToRetrieveTestStatusPerTestDefinition(
            $milestone,
            $test_definitions,
            $user->getUgroups($project->getID(), []),
            $test_exec_status_field_id,
            $test_exec_art_link_field_id,
            $test_campaign_art_link_field_id,
        );
    }

    private function getTestExecutionStatusFieldID(\Project $project, \PFUser $user): ?int
    {
        $test_exec_tracker_id = $this->testmanagement_config->getTestExecutionTrackerId($project);

        if ($test_exec_tracker_id === false) {
            return null;
        }

        $test_exec_tracker = $this->tracker_factory->getTrackerById($test_exec_tracker_id);
        if (! $this->canUserViewTracker($test_exec_tracker, $user)) {
            return null;
        }

        $status_field = $test_exec_tracker->getStatusField();
        if ($status_field === null || ! $status_field->userCanRead($user)) {
            return null;
        }

        return (int) $status_field->getId();
    }

    /**
     * @param int|false $tracker_id
     */
    private function getReadableByTheUserArtLinkFieldID($tracker_id, \PFUser $user): ?int
    {
        if ($tracker_id === false) {
            return null;
        }

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $this->canUserViewTracker($tracker, $user)) {
            return null;
        }

        $art_link_field = $this->form_element_factory->getAnArtifactLinkField($user, $tracker);

        if ($art_link_field === null) {
            return null;
        }

        return (int) $art_link_field->getId();
    }

    /**
     * @psalm-assert-if-true !null $tracker
     */
    private function canUserViewTracker(?\Tracker $tracker, \PFUser $user): bool
    {
        if ($tracker === null) {
            return false;
        }

        return $tracker->userCanView($user);
    }
}
