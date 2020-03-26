<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration;

use Tuleap\DB\DataAccessObject;

class JenkinsServerDao extends DataAccessObject
{
    public function addJenkinsServer(int $project_id, string $jenkins_server_url): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_hudson_git_project_server',
            [
                'project_id' => $project_id,
                'jenkins_server_url' => $jenkins_server_url,
            ],
            [
                'jenkins_server_url'
            ]
        );
    }

    public function getJenkinsServerOfProject(int $project_id): array
    {
        $sql = "SELECT *
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?";

        return $this->getDB()->run($sql, $project_id);
    }

    public function getPaginatedJenkinsServerOfProject(
        int $project_id,
        int $limit,
        int $offset
    ) : array {
        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?
                LIMIT ? OFFSET ?
                ";

        return $this->getDB()->run($sql, $project_id, $limit, $offset);
    }

    public function isJenkinsServerAlreadyDefinedInProject(int $project_id, string $jenkins_server_url): bool
    {
        $sql = "SELECT NULL
                FROM plugin_hudson_git_project_server
                WHERE project_id = ?
                AND jenkins_server_url = ?";

        $rows = $this->getDB()->row($sql, $project_id, $jenkins_server_url);

        if (empty($rows)) {
            return false;
        }

        return count($rows) > 0;
    }

    public function getJenkinsServerById(int $jenkins_server_id): ?array
    {
        $sql = "SELECT *
                FROM plugin_hudson_git_project_server
                WHERE id = ?";

        return $this->getDB()->row($sql, $jenkins_server_id);
    }

    public function deleteJenkinsServer(int $jenkins_server_id): void
    {
        $this->getDB()->delete(
            'plugin_hudson_git_project_server',
            [
                'id' => $jenkins_server_id
            ]
        );
    }
}
