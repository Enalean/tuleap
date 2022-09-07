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

use Project;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\Group\GitlabGroupApiDataRepresentation;
use Tuleap\Gitlab\Group\BuildGitlabGroup;
use Tuleap\Gitlab\Group\GitlabGroupAlreadyExistsException;
use Tuleap\Gitlab\Group\GitlabGroupDBInsertionRepresentation;
use Tuleap\Gitlab\Group\Token\InsertGroupToken;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;

final class GitlabRepositoryGroupLinkHandler implements HandleGitlabRepositoryGroupLink
{
    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private VerifyGitlabRepositoryIsIntegrated $verify_gitlab_repository_is_integrated,
        private CreateGitlabRepositories $gitlab_repository_creator,
        private BuildGitlabGroup $gitlab_group_factory,
        private InsertGroupToken $group_token_inserter,
    ) {
    }

    /**
     * @param GitlabProject[] $gitlab_projects
     *
     * @throws GitlabGroupAlreadyExistsException
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    public function integrateGitlabRepositoriesInProject(
        Credentials $credentials,
        array $gitlab_projects,
        Project $project,
        GitlabRepositoryCreatorConfiguration $configuration,
        GitlabGroupApiDataRepresentation $api_data_representation,
    ): GitlabGroupRepresentation {
        return $this->db_transaction_executor->execute(
            function () use ($credentials, $gitlab_projects, $project, $configuration, $api_data_representation) {
                $gitlab_group_for_insertion = GitlabGroupDBInsertionRepresentation::buildGitlabGroupToInsertFromGitlabApi($api_data_representation);
                $gitlab_group               = $this->gitlab_group_factory->createGroup($gitlab_group_for_insertion);

                $this->group_token_inserter->insertToken($gitlab_group, $credentials->getApiToken()->getToken());

                $integrated_repositories = [];
                foreach ($gitlab_projects as $gitlab_project) {
                    $gitlab_repository_id = $gitlab_project->getId();
                    $gitlab_web_url       = $gitlab_project->getWebUrl();
                    $project_id           = (int) $project->getID();

                    $already_existing_gitlab_repository = $this->verify_gitlab_repository_is_integrated->isTheGitlabRepositoryAlreadyIntegratedInProject(
                        $project_id,
                        $gitlab_repository_id,
                        $gitlab_web_url
                    );

                    if (! $already_existing_gitlab_repository) {
                        $integrated_repositories[] = $this->gitlab_repository_creator->createGitlabRepositoryIntegration(
                            $credentials,
                            $gitlab_project,
                            $project,
                            $configuration
                        );
                    }
                }
                return new GitlabGroupRepresentation($gitlab_group->id, count($integrated_repositories));
            }
        );
    }
}
