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

namespace Tuleap\Gitlab\Group;

use Luracast\Restler\RestException;
use Project;
use Tuleap\Gitlab\API\BuildGitlabProjects;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\Group\RetrieveGitlabGroupInformation;
use Tuleap\Gitlab\Repository\GitlabRepositoryCreatorConfiguration;
use Tuleap\Gitlab\Repository\HandleGitlabRepositoryGroupLink;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupPOSTRepresentation;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;

final class GroupCreator
{
    public function __construct(
        private BuildGitlabProjects $build_gitlab_project,
        private RetrieveGitlabGroupInformation $gitlab_group_information_retriever,
        private HandleGitlabRepositoryGroupLink $repository_group_link_handler,
    ) {
    }

    /**
     * @throws RestException
     */
    public function createGroupAndIntegrations(Credentials $credentials, GitlabGroupPOSTRepresentation $gitlab_group_representation, Project $project): GitlabGroupRepresentation
    {
        try {
            $gitlab_group_information = $this->gitlab_group_information_retriever->getGitlabGroupFromGitlabApi($credentials, $gitlab_group_representation);
            $gitlab_group_projects    = $this->build_gitlab_project->getGroupProjectsFromGitlabAPI(
                $credentials,
                $gitlab_group_representation->gitlab_group_id
            );
            return $this->repository_group_link_handler->integrateGitlabRepositoriesInProject(
                $credentials,
                $gitlab_group_projects,
                $project,
                GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration(),
                $gitlab_group_information
            );
        } catch (
            GitlabGroupAlreadyExistsException |
            GitlabRequestException |
            GitlabResponseAPIException $exception
        ) {
            throw new RestException(400, $exception->getMessage());
        }
    }
}
