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

use DataAccessObject;

class Dao extends DataAccessObject {

    public function searchByProjectId($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM plugin_testmanagement WHERE project_id = $project_id";

        return $this->retrieve($sql);
    }


    public function countTestsExecutionsArtifacts()
    {
        $sql = "SELECT count(*) AS nb
                FROM plugin_testmanagement
                INNER JOIN tracker_artifact
                    ON plugin_testmanagement.test_execution_tracker_id = tracker_artifact.tracker_id";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function countTestExecutionsArtifactsRegisteredBefore($timestamp)
    {
        $timestamp = $this->da->escapeInt($timestamp);
        $sql       = "SELECT count(*) AS nb
                FROM plugin_testmanagement
                INNER JOIN tracker_artifact
                    ON plugin_testmanagement.test_execution_tracker_id = tracker_artifact.tracker_id
                WHERE submitted_on >= $timestamp";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function saveProjectConfig(
        $project_id,
        $campaign_tracker_id,
        $test_definition_tracker_id,
        $test_execution_tracker_id,
        $issue_tracker_id
    ) {
        $project_id                 = $this->da->escapeInt($project_id);
        $campaign_tracker_id        = $this->da->escapeInt($campaign_tracker_id);
        $test_definition_tracker_id = $this->da->escapeInt($test_definition_tracker_id);
        $test_execution_tracker_id  = $this->da->escapeInt($test_execution_tracker_id);
        $issue_tracker_id           = $this->da->escapeInt($issue_tracker_id);

        $sql = "REPLACE INTO plugin_testmanagement (project_id, campaign_tracker_id, test_definition_tracker_id, test_execution_tracker_id, issue_tracker_id)
                VALUES ($project_id, $campaign_tracker_id, $test_definition_tracker_id, $test_execution_tracker_id, $issue_tracker_id)";

        return $this->update($sql);
    }
}
