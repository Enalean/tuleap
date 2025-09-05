<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\API;

use DateTimeImmutable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

class GitlabProjectBuilder implements BuildGitlabProjects
{
    public function __construct(private WrapGitlabClient $gitlab_api_client)
    {
    }

    /**
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    public function getProjectFromGitlabAPI(Credentials $credentials, int $gitlab_repository_id): GitlabProject
    {
        $gitlab_project_data = $this->gitlab_api_client->getUrl($credentials, "/projects/$gitlab_repository_id");

        if (! $gitlab_project_data) {
            throw new GitlabResponseAPIException(
                'The query is not in error but the json content is empty. This is not expected.'
            );
        }

        return $this->buildGitlabProject($gitlab_project_data)
            ->match(
                fn(GitlabProject $project) => $project,
                function (Fault $fault) {
                    throw new GitlabResponseAPIException((string) $fault);
                }
            );
    }

    /**
     * @return GitlabProject[]
     * @throws GitlabRequestException
     * @throws GitlabResponseAPIException
     */
    #[\Override]
    public function getGroupProjectsFromGitlabAPI(Credentials $credentials, int $gitlab_group_id): array
    {
        $group_projects_data = $this->gitlab_api_client->getPaginatedUrl($credentials, '/groups/' . $gitlab_group_id . '/projects');

        if (! isset($group_projects_data)) {
            throw new GitlabResponseAPIException(
                'The query is not in error but the json content is empty. This is not expected.'
            );
        }

        $gitlab_projects = [];
        foreach ($group_projects_data as $gitlab_project) {
            $this->buildGitlabProject($gitlab_project)
                ->match(
                    function (GitlabProject $project) use (&$gitlab_projects) {
                        $gitlab_projects[] = $project;
                    },
                    function (Fault $fault) {
                        // This GitLab project may have Repository feature disabled, skip it from group
                        if (! $fault instanceof DefaultBranchMissingFromJSONFault) {
                            throw new GitlabResponseAPIException((string) $fault);
                        }
                    }
                );
        }
        return $gitlab_projects;
    }

    private function buildGitlabProject(array $gitlab_project_data): OK|Err
    {
        if (! array_key_exists('default_branch', $gitlab_project_data)) {
            return Result::err(DefaultBranchMissingFromJSONFault::build());
        }

        if (
            ! array_key_exists('id', $gitlab_project_data) ||
            ! array_key_exists('description', $gitlab_project_data) ||
            ! array_key_exists('web_url', $gitlab_project_data) ||
            ! array_key_exists('path_with_namespace', $gitlab_project_data) ||
            ! array_key_exists('last_activity_at', $gitlab_project_data)
        ) {
            return Result::err(GitLabProjectJSONFault::buildForMissingKey());
        }

        if (
            ! is_int($gitlab_project_data['id']) ||
            ! (is_string($gitlab_project_data['description']) || $gitlab_project_data['description'] === null) ||
            ! is_string($gitlab_project_data['web_url']) ||
            ! is_string($gitlab_project_data['path_with_namespace']) ||
            ! is_string($gitlab_project_data['last_activity_at']) ||
            ! is_string($gitlab_project_data['default_branch'])
        ) {
            return Result::err(GitLabProjectJSONFault::buildForIncorrectType());
        }

        return Result::ok(new GitlabProject(
            $gitlab_project_data['id'],
            (string) $gitlab_project_data['description'],
            $gitlab_project_data['web_url'],
            $gitlab_project_data['path_with_namespace'],
            new DateTimeImmutable($gitlab_project_data['last_activity_at']),
            $gitlab_project_data['default_branch']
        ));
    }
}
