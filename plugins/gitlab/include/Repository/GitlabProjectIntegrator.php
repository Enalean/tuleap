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
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabProject;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabRequestFault;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\API\GitlabResponseAPIFault;
use Tuleap\Gitlab\Artifact\Action\SaveIntegrationBranchPrefix;
use Tuleap\Gitlab\Group\GroupLink;
use Tuleap\Gitlab\Group\IntegrateRepositoriesInGroupLinkCommand;
use Tuleap\Gitlab\Group\LinkARepositoryIntegrationToAGroup;
use Tuleap\Gitlab\Group\NewRepositoryIntegrationLinkedToAGroup;
use Tuleap\Gitlab\Group\VerifyRepositoryIntegrationsAlreadyLinked;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class GitlabProjectIntegrator implements IntegrateGitlabProject
{
    public function __construct(
        private CreateGitlabRepositories $gitlab_repository_creator,
        private SaveIntegrationBranchPrefix $branch_prefix_saver,
        private LinkARepositoryIntegrationToAGroup $link_integration_to_group,
        private VerifyRepositoryIntegrationsAlreadyLinked $verify_repository_integrations_already_linked,
        private RepositoryIntegrationRetriever $repository_integration_retriever,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    #[\Override]
    public function integrateSeveralProjects(IntegrateRepositoriesInGroupLinkCommand $command): Ok|Err
    {
        foreach ($command->gitlab_projects as $gitlab_project) {
            $result = $this->linkIntegrationToGroup($gitlab_project, $command->project, $command->credentials, $command->group_link);
            if (Result::isErr($result)) {
                return $result;
            }
        }
        return Result::ok(null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function linkIntegrationToGroup(
        GitlabProject $gitlab_project,
        Project $project,
        Credentials $credentials,
        GroupLink $gitlab_group,
    ): Ok|Err {
        return $this->repository_integration_retriever->getOneIntegration($project, $gitlab_project)
            ->orElse(fn() => $this->createRepositoryIntegration($gitlab_project, $project, $credentials, $gitlab_group))
            ->map(function (GitlabRepositoryIntegration $integration) use ($gitlab_group) {
                $is_integration_already_linked_to_a_group = $this->verify_repository_integrations_already_linked->isRepositoryIntegrationAlreadyLinkedToAGroup(
                    $integration->getId()
                );
                if (! $is_integration_already_linked_to_a_group) {
                    $this->link_integration_to_group->linkARepositoryIntegrationToAGroup(
                        new NewRepositoryIntegrationLinkedToAGroup($integration->getId(), $gitlab_group->id)
                    );
                }
                return null;
            });
    }

    /**
     * @return Ok<GitlabRepositoryIntegration> | Err<Fault>
     */
    private function createRepositoryIntegration(
        GitlabProject $gitlab_project,
        Project $project,
        Credentials $credentials,
        GroupLink $gitlab_group,
    ): Ok|Err {
        $configuration = $gitlab_group->allow_artifact_closure
            ? GitlabRepositoryCreatorConfiguration::buildConfigurationAllowingArtifactClosure()
            : GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();
        try {
            $integration = $this->gitlab_repository_creator->createGitlabRepositoryIntegration(
                $credentials,
                $gitlab_project,
                $project,
                $configuration
            );
        } catch (GitlabRequestException $e) {
            return Result::err(GitlabRequestFault::fromGitlabRequestException($e));
        } catch (GitlabResponseAPIException $e) {
            return Result::err(GitlabResponseAPIFault::fromGitlabResponseAPIException($e));
        }
        if ($gitlab_group->prefix_branch_name !== '') {
            $this->branch_prefix_saver->setCreateBranchPrefixForIntegration(
                $integration->getId(),
                $gitlab_group->prefix_branch_name
            );
        }
        return Result::ok($integration);
    }
}
