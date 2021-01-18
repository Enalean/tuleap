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

use DateTimeImmutable;
use Project;
use Tuleap\Gitlab\API\GitlabProject;

class GitlabRepositoryFactory
{
    /**
     * @var GitlabRepositoryDao
     */
    private $dao;

    public function __construct(GitlabRepositoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @return GitlabRepository[]
     */
    public function getGitlabRepositoriesForProject(Project $project): array
    {
        $gitlab_repositories = [];
        foreach ($this->dao->getGitlabRepositoriesForProject((int) $project->getID()) as $row) {
            $gitlab_repositories[] = $this->getInstanceFromRow($row);
        }
        return $gitlab_repositories;
    }

    public function getGitlabRepositoryByNameInProject(Project $project, string $gitlab_repository_name): ?GitlabRepository
    {
        $row = $this->dao->getGitlabRepositoryByNameInProject($gitlab_repository_name, (int) $project->getID());
        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    public function getGitlabRepositoryById(int $id): ?GitlabRepository
    {
        $row = $this->dao->searchGitlabRepositoryById($id);
        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    public function getGitlabRepositoryByGitlabRepositoryIdAndPath(int $gitlab_repository_id, string $http_path): ?GitlabRepository
    {
        $row = $this->dao->searchGitlabRepositoryByGitlabRepositoryIdAndPath($gitlab_repository_id, $http_path);
        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    public function getGitlabRepositoryByGitlabProjectAndId(
        GitlabProject $gitlab_project,
        int $id
    ): GitlabRepository {
        return new GitlabRepository(
            $id,
            $gitlab_project->getId(),
            $gitlab_project->getPathWithNamespace(),
            $gitlab_project->getDescription(),
            $gitlab_project->getWebUrl(),
            $gitlab_project->getLastActivityAt(),
        );
    }

    /**
     * @param array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int} $row
     */
    private function getInstanceFromRow(array $row): GitlabRepository
    {
        return new GitlabRepository(
            $row['id'],
            $row['gitlab_repository_id'],
            $row['name'],
            (string) $row['description'],
            $row['gitlab_repository_url'],
            (new DateTimeImmutable())->setTimestamp($row['last_push_date'])
        );
    }
}
