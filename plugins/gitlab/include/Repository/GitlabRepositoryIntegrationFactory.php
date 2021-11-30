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
use ProjectManager;
use Tuleap\Gitlab\API\GitlabProject;

class GitlabRepositoryIntegrationFactory
{
    /**
     * @var GitlabRepositoryIntegrationDao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(GitlabRepositoryIntegrationDao $dao, ProjectManager $project_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
    }

    /**
     * @return GitlabRepositoryIntegration[]
     */
    public function getAllIntegrationsInProject(Project $project): array
    {
        $gitlab_repositories = [];
        foreach ($this->dao->searchAllIntegrationsInProject((int) $project->getID()) as $row) {
            $gitlab_repositories[] = $this->getInstanceFromRow($row);
        }
        return $gitlab_repositories;
    }

    public function getIntegrationByNameInProject(Project $project, string $gitlab_repository_name): ?GitlabRepositoryIntegration
    {
        $row = $this->dao->searchIntegrationByNameInProject($gitlab_repository_name, (int) $project->getID());
        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    public function getIntegrationById(int $id): ?GitlabRepositoryIntegration
    {
        $row = $this->dao->searchIntegrationById($id);
        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    /**
     * @return GitlabRepositoryIntegration[]
     */
    public function getIntegrationsByGitlabRepositoryIdAndPath(int $gitlab_repository_id, string $http_path): array
    {
        $rows = $this->dao->searchIntegrationsByGitlabRepositoryIdAndPath($gitlab_repository_id, $http_path);
        if ($rows === null) {
            return [];
        }

        $gitlab_repositories = [];
        foreach ($rows as $row) {
            $gitlab_repositories[] = $this->getInstanceFromRow($row);
        }

        return $gitlab_repositories;
    }

    public function createRepositoryIntegration(
        GitlabProject $gitlab_project,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
    ): GitlabRepositoryIntegration {
        $id = $this->dao->createGitlabRepositoryIntegration(
            $gitlab_project->getId(),
            $gitlab_project->getPathWithNamespace(),
            $gitlab_project->getDescription(),
            $gitlab_project->getWebUrl(),
            $gitlab_project->getLastActivityAt()->getTimestamp(),
            (int) $project->getID(),
            $configuration->isRepositoryIntegrationAllowingArtifactClosure()
        );

        return new GitlabRepositoryIntegration(
            $id,
            $gitlab_project->getId(),
            $gitlab_project->getPathWithNamespace(),
            $gitlab_project->getDescription(),
            $gitlab_project->getWebUrl(),
            (new DateTimeImmutable())->setTimestamp(
                $gitlab_project->getLastActivityAt()->getTimestamp()
            ),
            $project,
            $configuration->isRepositoryIntegrationAllowingArtifactClosure()
        );
    }

    /**
     * @param array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int} $row
     */
    private function getInstanceFromRow(array $row): GitlabRepositoryIntegration
    {
        $project = $this->project_manager->getProject($row['project_id']);

        return new GitlabRepositoryIntegration(
            $row['id'],
            $row['gitlab_repository_id'],
            $row['name'],
            $row['description'],
            $row['gitlab_repository_url'],
            (new DateTimeImmutable())->setTimestamp($row['last_push_date']),
            $project,
            (bool) $row['allow_artifact_closure']
        );
    }
}
