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
use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Branch\InvalidBranchNameException;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Artifact\Action\SaveIntegrationBranchPrefix;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\GitlabGroupAlreadyLinkedToProjectException;
use Tuleap\Gitlab\Group\GitlabGroupFactory;
use Tuleap\Gitlab\Group\NewGroup;
use Tuleap\Gitlab\Group\LinkARepositoryIntegrationToAGroup;
use Tuleap\Gitlab\Group\NewRepositoryIntegrationLinkedToAGroup;
use Tuleap\Gitlab\Group\ProjectAlreadyLinkedToGitlabGroupException;
use Tuleap\Gitlab\Group\Token\InsertGroupToken;
use Tuleap\Gitlab\REST\v1\Group\GitlabGroupRepresentation;
use Tuleap\NeverThrow\Result;

final class GitlabRepositoryGroupLinkHandler
{
    private const FAKE_BRANCH_NAME = 'branch_name';

    public function __construct(
        private DBTransactionExecutor $db_transaction_executor,
        private CreateGitlabRepositories $gitlab_repository_creator,
        private GitlabGroupFactory $gitlab_group_factory,
        private InsertGroupToken $group_token_inserter,
        private LinkARepositoryIntegrationToAGroup $link_integration_to_group,
        private SaveIntegrationBranchPrefix $branch_prefix_saver,
        private RepositoryIntegrationRetriever $repository_integration_retriever,
    ) {
    }

    /**
     * @param GitlabProject[] $gitlab_projects
     *
     * @throws GitlabGroupAlreadyLinkedToProjectException
     * @throws ProjectAlreadyLinkedToGitlabGroupException
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     * @throws InvalidBranchNameException
     */
    public function integrateGitlabRepositoriesInProject(
        Credentials $credentials,
        array $gitlab_projects,
        Project $project,
        NewGroup $new_group,
    ): GitlabGroupRepresentation {
        BranchName::fromBranchNameShortHand($new_group->prefix_branch_name . self::FAKE_BRANCH_NAME);

        return $this->db_transaction_executor->execute(
            function () use ($credentials, $gitlab_projects, $project, $new_group) {
                $gitlab_group = $this->gitlab_group_factory->createGroup($new_group);

                $this->group_token_inserter->insertToken($gitlab_group, $credentials->getApiToken()->getToken());

                $number_of_integrated_repositories = 0;
                foreach ($gitlab_projects as $gitlab_project) {
                    $this->integrateOneProject($gitlab_project, $project, $credentials, $gitlab_group);
                    $number_of_integrated_repositories++;
                }
                return new GitlabGroupRepresentation($gitlab_group->id, $number_of_integrated_repositories);
            }
        );
    }

    /**
     * @throws GitlabResponseAPIException
     * @throws GitlabRequestException
     */
    private function integrateOneProject(
        GitlabProject $gitlab_project,
        Project $project,
        Credentials $credentials,
        GroupLink $gitlab_group,
    ): void {
        $integration_result = $this->repository_integration_retriever->getOneIntegration($project, $gitlab_project);

        if (Result::isOk($integration_result)) {
            $integration = $integration_result->value;
        } else {
            $configuration = $gitlab_group->allow_artifact_closure
                ? GitlabRepositoryCreatorConfiguration::buildConfigurationAllowingArtifactClosure()
                : GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();
            $integration   = $this->gitlab_repository_creator->createGitlabRepositoryIntegration(
                $credentials,
                $gitlab_project,
                $project,
                $configuration
            );
            if ($gitlab_group->prefix_branch_name !== '') {
                $this->branch_prefix_saver->setCreateBranchPrefixForIntegration(
                    $integration->getId(),
                    $gitlab_group->prefix_branch_name
                );
            }
        }

        $this->link_integration_to_group->linkARepositoryIntegrationToAGroup(
            new NewRepositoryIntegrationLinkedToAGroup(
                $integration->getId(),
                $gitlab_group->id
            )
        );
    }
}
