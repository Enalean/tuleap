<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use DateTimeImmutable;
use Project;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class RepositoryIntegrationRetriever
{
    public function __construct(private RetrieveIntegrationDao $retrieve_integrations)
    {
    }

    /**
     * @return Ok<GitlabRepositoryIntegration> | Err<Fault>
     */
    public function getOneIntegration(Project $project, GitlabProject $gitlab_project): Ok|Err
    {
        $row = $this->retrieve_integrations->searchUniqueIntegration($project, $gitlab_project);
        if ($row === null) {
            return Result::err(RepositoryIntegrationNotFoundFault::build($project, $gitlab_project));
        }
        return Result::ok($this->getInstanceFromRowAndProject($row, $project));
    }

    /**
     * @param array{id:int, gitlab_repository_id:int, name:string, description:string, gitlab_repository_url:string, last_push_date:int, project_id:int, allow_artifact_closure:int} $row
     */
    private function getInstanceFromRowAndProject(array $row, Project $project): GitlabRepositoryIntegration
    {
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
