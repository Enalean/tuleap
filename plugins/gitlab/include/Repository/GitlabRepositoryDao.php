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

use Tuleap\DB\DataAccessObject;

class GitlabRepositoryDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{id:int, gitlab_repository_id:int, name:string, description:string, full_url:string, last_push_date:int}>
     */
    public function getGitlabRepositoriesForProject(int $project_id): array
    {
        $sql = 'SELECT plugin_gitlab_repository.*
                FROM plugin_gitlab_repository
                    INNER JOIN plugin_gitlab_repository_project
                        ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                WHERE plugin_gitlab_repository_project.project_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, full_url:string, last_push_date:int}
     */
    public function getGitlabRepositoryByNameInProject(string $name, int $project_id): ?array
    {
        $sql = 'SELECT plugin_gitlab_repository.*
                FROM plugin_gitlab_repository
                    INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                WHERE plugin_gitlab_repository.name = ?
                    AND plugin_gitlab_repository_project.project_id = ?';

        return $this->getDB()->row($sql, $name, $project_id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, full_url:string, last_push_date:int}
     */
    public function searchGitlabRepositoryById(int $id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository
                WHERE id = ?';

        return $this->getDB()->row($sql, $id);
    }

    /**
     * @psalm-return array{id:int, gitlab_repository_id:int, name:string, description:string, full_url:string, last_push_date:int}
     */
    public function searchGitlabRepositoryByGitlabRepositoryIdAndPath(int $gitlab_repository_id, string $http_path): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository
                WHERE gitlab_repository_id = ?
                    AND full_url = ?';

        return $this->getDB()->row($sql, $gitlab_repository_id, $http_path);
    }

    public function updateLastPushDateForRepository(int $repository_id, int $last_update_date): void
    {
        $this->getDB()->update(
            'plugin_gitlab_repository',
            ['last_push_date' => $last_update_date],
            ['id' => $repository_id]
        );
    }

    public function deleteGitlabRepository(int $repository_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository',
            ['id' => $repository_id]
        );
    }

    public function createGitlabRepository(
        int $gitlab_repository_id,
        string $name,
        string $description,
        string $full_url,
        int $last_push_date
    ): int {
        return (int) $this->getDB()->insertReturnId(
            'plugin_gitlab_repository',
            [
                'gitlab_repository_id' => $gitlab_repository_id,
                'name'                 => $name,
                'description'          => $description,
                'full_url'             => $full_url,
                'last_push_date'       => $last_push_date,
            ]
        );
    }

    public function isAGitlabRepositoryWithSameNameAlreadyIntegratedInProject(
        string $name,
        string $web_url,
        int $project_id
    ): bool {
        $sql = "SELECT NULL
                FROM plugin_gitlab_repository
                    INNER JOIN plugin_gitlab_repository_project ON (plugin_gitlab_repository.id = plugin_gitlab_repository_project.id)
                WHERE plugin_gitlab_repository.name = ?
                    AND plugin_gitlab_repository.full_url != ?
                    AND plugin_gitlab_repository_project.project_id = ?";

        $rows = $this->getDB()->run($sql, $name, $web_url, $project_id);

        return count($rows) > 0;
    }
}
