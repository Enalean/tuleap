<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Administration;

use Project;
use TrackerFactory;
use Tuleap\TestManagement\MissingArtifactLinkException;
use Tuleap\TestManagement\TestmanagementTrackersConfiguration;
use Tuleap\TestManagement\TrackerDefinitionNotValidException;
use Tuleap\TestManagement\TrackerExecutionNotValidException;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;

class TrackerChecker
{
    /**
     * @var array
     */
    private $project_trackers = [];

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var FrozenFieldsDao
     */
    private $frozen_fields_dao;

    /**
     * @var HiddenFieldsetsDao
     */
    private $hidden_fieldsets_dao;
    /**
     * @var FieldUsageDetector
     */
    private $field_usage_detector;

    public function __construct(
        TrackerFactory $tracker_factory,
        FrozenFieldsDao $frozen_fields_dao,
        HiddenFieldsetsDao $hidden_fieldsets_dao,
        FieldUsageDetector $field_usage_detector,
    ) {
        $this->tracker_factory      = $tracker_factory;
        $this->frozen_fields_dao    = $frozen_fields_dao;
        $this->hidden_fieldsets_dao = $hidden_fieldsets_dao;
        $this->field_usage_detector = $field_usage_detector;
    }

    /**
     * @throws MissingArtifactLinkException
     * @throws TrackerDefinitionNotValidException
     * @throws TrackerDoesntExistException
     * @throws TrackerExecutionNotValidException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws TrackerIsDeletedException
     * @throws TrackerNotInProjectException
     */
    public function checkTrackers(Project $project, TestmanagementTrackersConfiguration $config_trackers): void
    {
        $this->checkSubmittedTrackerCanBeUsed(
            $project,
            $config_trackers->getCampaign()->getTrackerId()
        );

        $this->checkSubmittedDefinitionTrackerCanBeUsed(
            $project,
            $config_trackers->getTestDefinition()->getTrackerId()
        );
        $this->checkSubmittedExecutionTrackerCanBeUsed(
            $project,
            $config_trackers->getTestExecution()->getTrackerId()
        );

        $issue_tracker = $config_trackers->getIssue();
        if ($issue_tracker && $issue_tracker->getTrackerId()) {
            $this->checkSubmittedTrackerCanBeUsed(
                $project,
                $issue_tracker->getTrackerId()
            );
        }
    }

    /**
     * @throws TrackerNotInProjectException
     * @throws TrackerDoesntExistException
     * @throws TrackerIsDeletedException
     */
    private function checkTrackerIsInProject(Project $project, int $submitted_id): void
    {
        $this->initTrackerIds($project);

        $tracker = $this->tracker_factory->getTrackerById($submitted_id);
        if (! $tracker) {
            throw new TrackerDoesntExistException();
        }
        if ($tracker->isDeleted()) {
            throw new TrackerIsDeletedException();
        }
        if (! array_key_exists($submitted_id, $this->project_trackers[$project->getID()])) {
            throw new TrackerNotInProjectException();
        }
    }

    /**
     * @throws MissingArtifactLinkException
     * @throws TrackerDoesntExistException
     * @throws TrackerIsDeletedException
     * @throws TrackerNotInProjectException
     */
    public function checkSubmittedTrackerCanBeUsed(Project $project, int $submitted_id): void
    {
        $this->checkTrackerIsInProject($project, $submitted_id);
        $this->checkTrackerHasArtifactLink($submitted_id);
    }

    private function initTrackerIds(Project $project): void
    {
        $project_id = $project->getID();

        if (! array_key_exists($project_id, $this->project_trackers)) {
            $this->project_trackers[$project_id] = $this->tracker_factory->getTrackersByGroupId($project_id);
        }
    }

    /**
     * @throws MissingArtifactLinkException
     * @throws TrackerDefinitionNotValidException
     * @throws TrackerDoesntExistException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws TrackerIsDeletedException
     * @throws TrackerNotInProjectException
     */
    public function checkSubmittedDefinitionTrackerCanBeUsed(Project $project, int $tracker_id): void
    {
        if (! $this->field_usage_detector->isStepDefinitionFieldUsed($tracker_id)) {
            throw new TrackerDefinitionNotValidException();
        }
        $this->checkPostActions($tracker_id);
        $this->checkSubmittedTrackerCanBeUsed($project, $tracker_id);
    }

    /**
     * @throws MissingArtifactLinkException
     * @throws TrackerDoesntExistException
     * @throws TrackerExecutionNotValidException
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     * @throws TrackerIsDeletedException
     * @throws TrackerNotInProjectException
     */
    public function checkSubmittedExecutionTrackerCanBeUsed(Project $project, int $tracker_id): void
    {
        if (! $this->field_usage_detector->isStepExecutionFieldUsed($tracker_id)) {
            throw new TrackerExecutionNotValidException();
        }
        $this->checkPostActions($tracker_id);
        $this->checkSubmittedTrackerCanBeUsed($project, $tracker_id);
    }

    /**
     * @throws TrackerHasAtLeastOneFrozenFieldsPostActionException
     * @throws TrackerHasAtLeastOneHiddenFieldsetsPostActionException
     */
    private function checkPostActions(int $submitted_id): void
    {
        if ($this->frozen_fields_dao->isAFrozenFieldPostActionUsedInTracker($submitted_id)) {
            throw new TrackerHasAtLeastOneFrozenFieldsPostActionException();
        }

        if ($this->hidden_fieldsets_dao->isAHiddenFieldsetPostActionUsedInTracker($submitted_id)) {
            throw new TrackerHasAtLeastOneHiddenFieldsetsPostActionException();
        }
    }

    /**
     * @throws MissingArtifactLinkException
     */
    private function checkTrackerHasArtifactLink(int $tracker_id): void
    {
        if (! $this->field_usage_detector->isArtifactLinksFieldUsed($tracker_id)) {
            throw new MissingArtifactLinkException();
        }
    }
}
