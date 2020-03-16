<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 * Continuous integration DAO for Git
 */
class Git_Ci_Dao extends DataAccessObject
{

    /**
     * Retrieve git trigger of a ci job
     *
     * @param int $jobId Id of the CI job
     *
     * @return DataAccessResult
     */
    public function retrieveTrigger($jobId)
    {
        $sql = 'SELECT repository_id
                FROM plugin_git_ci
                WHERE job_id = ' . $this->da->escapeInt($jobId);
        return $this->retrieve($sql);
    }

    /**
     * Retrieve git triggers of a project
     *
     * @param int $projectId Id of the project
     *
     * @return DataAccessResult
     */
    public function retrieveTriggers($projectId)
    {
        $sql = 'SELECT job_id
                FROM plugin_git_ci
                JOIN plugin_git USING(repository_id)
                WHERE project_id = ' . $this->da->escapeInt($projectId);
        return $this->retrieve($sql);
    }

    /**
     * Retrieve Jobs path corresponding to the repository
     *
     * @param int $repositoryId Id of the repository
     *
     * @return DataAccessResult
     */
    public function retrieveTriggersPathByRepository($repositoryId)
    {
        $sql = 'SELECT job_url, token
                FROM plugin_hudson_job
                JOIN plugin_git_ci USING(job_id)
                WHERE repository_id = ' . $this->da->escapeInt($repositoryId);
        return $this->retrieve($sql);
    }

    /**
     * Check that the repository exist and belong to the same project as the ci job
     *
     * @param int $jobId Id of the CI job
     * @param int $repositoryId Id of the repository
     *
     * @return DataAccessResult
     */
    public function checkRepository($jobId, $repositoryId)
    {
        $sql = 'SELECT job_id
                FROM plugin_hudson_job
                JOIN plugin_git ON (group_id = project_id)
                WHERE repository_id = ' . $this->da->escapeInt($repositoryId) . '
                AND job_id = ' . $this->da->escapeInt($jobId);
        return $this->retrieve($sql);
    }

    /**
     * Save a new trigger
     *
     * @param int $jobId Id of the CI job
     * @param int $repositoryId Id of the repository
     *
     * @return bool
     */
    public function saveTrigger($jobId, $repositoryId)
    {
        $sql = 'REPLACE INTO plugin_git_ci
                (
                job_id,
                repository_id
                )
                VALUES
                (
                ' . $this->da->escapeInt($jobId) . ',
                ' . $this->da->escapeInt($repositoryId) . '
                )';
        return $this->update($sql);
    }

    /**
     * Delete trigger
     *
     * @param int $jobId Id of the CI job
     *
     * @return bool
     */
    public function deleteTrigger($jobId)
    {
        $sql = 'DELETE FROM plugin_git_ci
                WHERE job_id = ' . $this->da->escapeInt($jobId);
        return $this->update($sql);
    }
}
