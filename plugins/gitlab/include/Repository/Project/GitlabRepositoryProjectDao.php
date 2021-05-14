<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Project;

use Tuleap\DB\DataAccessObject;

class GitlabRepositoryProjectDao extends DataAccessObject
{
    /**
     * @return int[]
     */
    public function searchProjectsTheGitlabRepositoryIsIntegratedIn(int $repository_id): array
    {
        $sql = "SELECT project_id FROM plugin_gitlab_repository_project WHERE id = ?";

        return $this->getDB()->column($sql, [$repository_id]);
    }

    public function removeGitlabRepositoryIntegrationInProject(int $repository_id, int $project_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_project',
            [
                'id'         => $repository_id,
                'project_id' => $project_id
            ]
        );
    }

    public function isGitlabRepositoryIntegratedInProject(int $repository_id, int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_gitlab_repository_project
                WHERE id = ?
                    AND project_id = ?";

        $rows = $this->getDB()->run($sql, $repository_id, $project_id);

        return count($rows) > 0;
    }

    public function isArtifactClosureActionEnabledForRepositoryInProject(int $repository_id, int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_gitlab_repository_project
                WHERE id = ?
                    AND project_id = ?
                    AND allow_artifact_closure = 1";

        $rows = $this->getDB()->run($sql, $repository_id, $project_id);

        return count($rows) > 0;
    }

    public function addGitlabRepositoryIntegrationInProject(
        int $repository_id,
        int $project_id,
        int $allow_artifact_closure
    ): void {
        $this->getDB()->insert(
            'plugin_gitlab_repository_project',
            [
                'id'                     => $repository_id,
                'project_id'             => $project_id,
                'allow_artifact_closure' => $allow_artifact_closure,
            ]
        );
    }
}
