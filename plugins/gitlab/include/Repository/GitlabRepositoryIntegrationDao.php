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

namespace Tuleap\Gitlab\Repository;

use Project;
use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\API\GitlabProject;

class GitlabRepositoryIntegrationDao extends DataAccessObject implements VerifyGitlabRepositoryIsIntegrated, RetrieveIntegrationDao
{
    /**
     * @psalm-return list<array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}>
     */
    public function searchAllIntegrationsInProject(int $project_id): array
    {
        $sql = 'SELECT plugin_gitlab_repository_integration.*
                FROM plugin_gitlab_repository_integration
                WHERE plugin_gitlab_repository_integration.project_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}
     */
    public function searchIntegrationByNameInProject(string $name, int $project_id): ?array
    {
        $sql = 'SELECT plugin_gitlab_repository_integration.*
                FROM plugin_gitlab_repository_integration
                WHERE plugin_gitlab_repository_integration.name = ?
                    AND plugin_gitlab_repository_integration.project_id = ?';

        return $this->getDB()->row($sql, $name, $project_id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}
     */
    public function searchIntegrationById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_integration
                WHERE id = ?';

        return $this->getDB()->row($sql, $id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}
     */
    #[\Override]
    public function searchUniqueIntegration(Project $project, GitlabProject $gitlab_project): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_integration
                WHERE gitlab_repository_id = ?
                    AND gitlab_repository_url = ?
                    AND project_id = ?';

        return $this->getDB()->row($sql, $gitlab_project->getId(), $gitlab_project->getWebUrl(), (int) $project->getID());
    }

    /**
     * @psalm-return list<array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int}>
     */
    public function searchIntegrationsByGitlabRepositoryIdAndPath(
        int $gitlab_repository_id,
        string $http_path,
    ): ?array {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_integration
                WHERE gitlab_repository_id = ?
                    AND gitlab_repository_url = ?';

        return $this->getDB()->run($sql, $gitlab_repository_id, $http_path);
    }

    public function updateLastPushDateForIntegration(int $integration_id, int $last_update_date): void
    {
        $this->getDB()->update(
            'plugin_gitlab_repository_integration',
            ['last_push_date' => $last_update_date],
            ['id' => $integration_id]
        );
    }

    public function deleteIntegration(int $integration_id): void
    {
        $this->getDB()->tryFlatTransaction(function () use ($integration_id): void {
            $this->getDB()->delete('plugin_gitlab_group_repository_integration', ['integration_id' => $integration_id]);
            $this->getDB()->delete('plugin_gitlab_repository_integration', ['id' => $integration_id]);
        });
    }

    public function createGitlabRepositoryIntegration(
        int $gitlab_repository_id,
        string $name,
        string $description,
        string $gitlab_repository_url,
        int $last_push_date,
        int $project_id,
        bool $allow_artifact_closure,
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlab_repository_integration',
            [
                'gitlab_repository_id'   => $gitlab_repository_id,
                'name'                   => $name,
                'description'            => $description,
                'gitlab_repository_url'  => $gitlab_repository_url,
                'last_push_date'         => $last_push_date,
                'project_id'             => $project_id,
                'allow_artifact_closure' => $allow_artifact_closure,
            ]
        );
    }

    public function isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject(
        string $name,
        string $web_url,
        int $project_id,
    ): bool {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration
                WHERE plugin_gitlab_repository_integration.name = ?
                    AND plugin_gitlab_repository_integration.gitlab_repository_url != ?
                    AND plugin_gitlab_repository_integration.project_id = ?';

        $rows = $this->getDB()->run($sql, $name, $web_url, $project_id);

        return count($rows) > 0;
    }

    #[\Override]
    public function isTheGitlabRepositoryAlreadyIntegratedInProject(
        int $project_id,
        int $gitlab_repository_id,
        string $http_path,
    ): bool {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration
                WHERE gitlab_repository_id = ?
                    AND gitlab_repository_url = ?
                    AND project_id = ?';

        $rows = $this->getDB()->run($sql, $gitlab_repository_id, $http_path, $project_id);

        return count($rows) > 0;
    }

    public function updateGitlabRepositoryIntegrationAllowArtifactClosureValue(
        int $id,
        bool $allow_artifact_closure,
    ): void {
        $this->getDB()->update(
            'plugin_gitlab_repository_integration',
            ['allow_artifact_closure' => $allow_artifact_closure],
            ['id' => $id]
        );
    }
}
