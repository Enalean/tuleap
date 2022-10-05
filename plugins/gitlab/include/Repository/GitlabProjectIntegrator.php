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
        private VerifyGitlabRepositoryIsIntegrated $verify_gitlab_repository_is_integrated,
        private CreateGitlabRepositories $gitlab_repository_creator,
        private SaveIntegrationBranchPrefix $branch_prefix_saver,
        private LinkARepositoryIntegrationToAGroup $link_integration_to_group,
        private VerifyRepositoryIntegrationsAlreadyLinked $verify_repository_integrations_already_linked,
    ) {
    }

    /**
     * @param GitlabProject[] $gitlab_projects
     * @return Ok<null>|Err<Fault>
     */
    public function integrateSeveralProjects(
        array $gitlab_projects,
        Project $project,
        Credentials $credentials,
        GroupLink $gitlab_group,
    ): Ok|Err {
        foreach ($gitlab_projects as $gitlab_project) {
            $result = $this->integrateOneProject($gitlab_project, $project, $credentials, $gitlab_group);
            if (Result::isErr($result)) {
                return Result::err($result->error);
            }
        }
        return Result::ok(null);
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    private function integrateOneProject(
        GitlabProject $gitlab_project,
        Project $project,
        Credentials $credentials,
        GroupLink $gitlab_group,
    ): Ok|Err {
        $gitlab_repository_id = $gitlab_project->getId();

        $already_existing_gitlab_repository = $this->verify_gitlab_repository_is_integrated->isTheGitlabRepositoryAlreadyIntegratedInProject(
            (int) $project->getID(),
            $gitlab_repository_id,
            $gitlab_project->getWebUrl()
        );

        $is_integration_already_linked_to_a_group = $this->verify_repository_integrations_already_linked->isRepositoryIntegrationAlreadyLinkedToAGroup($gitlab_repository_id);

        if ($already_existing_gitlab_repository && $is_integration_already_linked_to_a_group) {
            return Result::ok(null);
        }

        if (! $already_existing_gitlab_repository) {
            $configuration = $gitlab_group->allow_artifact_closure
                ? GitlabRepositoryCreatorConfiguration::buildConfigurationAllowingArtifactClosure()
                : GitlabRepositoryCreatorConfiguration::buildDefaultConfiguration();
            try {
                $new_integration = $this->gitlab_repository_creator->createGitlabRepositoryIntegration(
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
            if ($gitlab_group->prefix_branch_name !== "") {
                $this->branch_prefix_saver->setCreateBranchPrefixForIntegration(
                    $new_integration->getId(),
                    $gitlab_group->prefix_branch_name
                );
            }
        }

        if (! $is_integration_already_linked_to_a_group) {
            $this->link_integration_to_group->linkARepositoryIntegrationToAGroup(
                new NewRepositoryIntegrationLinkedToAGroup(
                    $gitlab_repository_id,
                    $gitlab_group->id
                )
            );
        }
        return Result::ok(null);
    }
}
