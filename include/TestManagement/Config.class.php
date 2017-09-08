<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

class Config {

    /** @var Dao */
    private $dao;

    /** @var array */
    private $cache_properties = array();

    public function __construct(Dao $dao) {
        $this->dao = $dao;
    }

    public function setProjectConfiguration(
        Project $project,
        $campaign_tracker_id,
        $test_definition_tracker_id,
        $test_execution_tracker_id,
        $issue_tracker_id
    ) {
        return $this->dao->saveProjectConfig($project->getId(), $campaign_tracker_id, $test_definition_tracker_id, $test_execution_tracker_id, $issue_tracker_id);
    }

    public function getCampaignTrackerId(Project $project) {
        return $this->getProperty($project, 'campaign_tracker_id');
    }

    public function getTestExecutionTrackerId(Project $project) {
        return $this->getProperty($project, 'test_execution_tracker_id');
    }

    public function getTestDefinitionTrackerId(Project $project) {
        return $this->getProperty($project, 'test_definition_tracker_id');
    }

    public function getIssueTrackerId(Project $project) {
        return $this->getProperty($project, 'issue_tracker_id');
    }

    private function getProperty(Project $project, $key) {
        $properties = $this->getPropertiesForProject($project);

        if (! isset($properties[$key])) {
            return false;
        }

        return $properties[$key];
    }

    private function getPropertiesForProject(Project $project) {
        if (! array_key_exists($project->getID(), $this->cache_properties)) {
            $this->cache_properties[$project->getID()] = $this->dao->searchByProjectId($project->getId())->getRow();
        }

        return $this->cache_properties[$project->getID()];
    }
}
