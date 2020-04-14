<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Project;
use TrackerFactory;

class Config
{

    /** @var Dao */
    private $dao;

    /**
     * @var array
     */
    private $properties = [];
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    public function __construct(Dao $dao, TrackerFactory $tracker_factory)
    {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    public function setProjectConfiguration(
        Project $project,
        int $campaign_tracker_id,
        int $test_definition_tracker_id,
        int $test_execution_tracker_id,
        ?int $issue_tracker_id
    ): bool {
        unset($this->properties[$project->getID()]);

        return $this->dao->saveProjectConfig(
            $project->getId(),
            $campaign_tracker_id,
            $test_definition_tracker_id,
            $test_execution_tracker_id,
            $issue_tracker_id
        );
    }

    /**
     * @return int|false
     */
    public function getCampaignTrackerId(Project $project)
    {
        return $this->getTrackerID($project, 'campaign_tracker_id');
    }

    /**
     * @return int|false
     */
    public function getTestExecutionTrackerId(Project $project)
    {
        return $this->getTrackerID($project, 'test_execution_tracker_id');
    }

    /**
     * @return int|false
     */
    public function getTestDefinitionTrackerId(Project $project)
    {
        return $this->getTrackerID($project, 'test_definition_tracker_id');
    }

    /**
     * @return int|null
     */
    public function getIssueTrackerId(Project $project)
    {
        $tacker_id =  $this->getTrackerID($project, 'issue_tracker_id');
        if (! $tacker_id) {
            return null;
        }

        return $tacker_id;
    }

    /**
     * @return int|false
     */
    private function getTrackerID(Project $project, string $key)
    {
        $id = $this->getProperty($project, $key);
        if ($id === false) {
            return $id;
        }

        $tracker = $this->tracker_factory->getTrackerById($id);
        if ($tracker !== null && $tracker->isActive()) {
            return (int) $id;
        }

        return false;
    }

    public function isConfigNeeded(Project $project): bool
    {
        return (! $this->getCampaignTrackerId($project)) ||
            (! $this->getTestDefinitionTrackerId($project)) ||
            (! $this->getTestExecutionTrackerId($project));
    }

    /**
     * @return false|mixed
     */
    private function getProperty(Project $project, string $key)
    {
        $project_id = $project->getID();

        if (! isset($this->properties[$project_id])) {
            $this->properties[$project_id] = $this->getPropertiesForProject($project);
        }

        if (! isset($this->properties[$project_id][$key])) {
            return false;
        }

        return $this->properties[$project_id][$key];
    }

    /**
     * @return array|false
     */
    private function getPropertiesForProject(Project $project)
    {
        return $this->dao->searchByProjectId($project->getId())->getRow();
    }
}
