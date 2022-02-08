<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Exception;
use Project;
use TrackerFactory;
use Tuleap\TestManagement\Config;

class AdminTrackersRetriever
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerChecker
     */
    private $tracker_checker;
    /**
     * @var Config
     */
    private $config;

    public function __construct(TrackerFactory $tracker_factory, TrackerChecker $tracker_checker, Config $config)
    {
        $this->tracker_factory = $tracker_factory;
        $this->tracker_checker = $tracker_checker;
        $this->config          = $config;
    }

    /**
     * @return AdminTrackerPresenter[]
     */
    private function getTrackersConfiguredForCampaignOrIssue(Project $project): array
    {
        $available_trackers = [];
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getGroupId()) as $tracker) {
            try {
                $this->tracker_checker->checkSubmittedTrackerCanBeUsed($project, $tracker->getId());
                $available_trackers[] = new AdminTrackerPresenter($tracker->getName(), $tracker->getId());
            } catch (Exception $exception) {
                continue;
            }
        }

        return $available_trackers;
    }

    /**
     * @return AdminTrackerPresenter[]
     */
    private function getTrackersConfiguredForTestExecution(Project $project): array
    {
        $available_trackers = [];
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getGroupId()) as $tracker) {
            try {
                $this->tracker_checker->checkSubmittedExecutionTrackerCanBeUsed($project, $tracker->getId());
                $available_trackers[] = new AdminTrackerPresenter($tracker->getName(), $tracker->getId());
            } catch (Exception $exception) {
                continue;
            }
        }

        return $available_trackers;
    }

    /**
     * @return AdminTrackerPresenter[]
     */
    private function getTrackersConfiguredForTestDefinition(Project $project): array
    {
        $available_trackers = [];
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getGroupId()) as $tracker) {
            try {
                $this->tracker_checker->checkSubmittedDefinitionTrackerCanBeUsed($project, $tracker->getId());
                $available_trackers[] = new AdminTrackerPresenter($tracker->getName(), $tracker->getId());
            } catch (Exception $exception) {
                continue;
            }
        }

        return $available_trackers;
    }

    public function retrieveAvailableTrackersForCampaign(Project $project): ListOfAdminTrackersPresenter
    {
        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);

        $campaign_tracker = null;
        if ($campaign_tracker_id) {
            $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        }

        return new ListOfAdminTrackersPresenter(
            $campaign_tracker ? new AdminTrackerPresenter(
                $campaign_tracker->getName(),
                $campaign_tracker->getId()
            ) : null,
            $this->getTrackersConfiguredForCampaignOrIssue($project)
        );
    }

    public function retrieveAvailableTrackersForExecution(Project $project): ListOfAdminTrackersPresenter
    {
        $execution_tracker_id = $this->config->getTestExecutionTrackerId($project);

        $execution_tracker = null;
        if ($execution_tracker_id) {
            $execution_tracker = $this->tracker_factory->getTrackerById($execution_tracker_id);
        }

        return new ListOfAdminTrackersPresenter(
            $execution_tracker ? new AdminTrackerPresenter(
                $execution_tracker->getName(),
                $execution_tracker->getId()
            ) : null,
            $this->getTrackersConfiguredForTestExecution($project)
        );
    }

    public function retrieveAvailableTrackersForDefinition(Project $project): ListOfAdminTrackersPresenter
    {
        $definition_tracker_id = $this->config->getTestDefinitionTrackerId($project);

        $definition_tracker = null;
        if ($definition_tracker_id) {
            $definition_tracker = $this->tracker_factory->getTrackerById($definition_tracker_id);
        }

        return new ListOfAdminTrackersPresenter(
            $definition_tracker ? new AdminTrackerPresenter(
                $definition_tracker->getName(),
                $definition_tracker->getId()
            ) : null,
            $this->getTrackersConfiguredForTestDefinition($project)
        );
    }

    public function retrieveAvailableTrackersForIssue(Project $project): ListOfAdminTrackersPresenter
    {
        $issue_tracker_id = $this->config->getIssueTrackerId($project);

        $issue_tracker = null;
        if ($issue_tracker_id) {
            $issue_tracker = $this->tracker_factory->getTrackerById($issue_tracker_id);
        }

        return new ListOfAdminTrackersPresenter(
            $issue_tracker ? new AdminTrackerPresenter($issue_tracker->getName(), $issue_tracker->getId()) : null,
            $this->getTrackersConfiguredForCampaignOrIssue($project)
        );
    }
}
