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

namespace Tuleap\Gitlab\REST\v1;

use Project;
use Tuleap\Gitlab\Artifact\Action\CreateBranchPrefixDao;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\WebhookDao;

class GitlabRepositoryRepresentationFactory
{
    private GitlabRepositoryIntegrationFactory $repository_integration_factory;
    private WebhookDao $webhook_dao;

    public function __construct(
        GitlabRepositoryIntegrationFactory $repository_integration_factory,
        WebhookDao $webhook_dao,
    ) {
        $this->repository_integration_factory = $repository_integration_factory;
        $this->webhook_dao                    = $webhook_dao;
    }

    /**
     * @return GitlabRepositoryRepresentation[]
     */
    public function getAllIntegrationsRepresentationsInProject(Project $project): array
    {
        $gitlab_repositories                 = $this->repository_integration_factory->getAllIntegrationsInProject($project);
        $gitlab_repositories_representations = [];
        foreach ($gitlab_repositories as $gitlab_repository) {
            $integration_webhook = $this->webhook_dao->getGitlabRepositoryWebhook(
                $gitlab_repository->getId()
            );

            $create_branch_prefix = (new CreateBranchPrefixDao())->searchCreateBranchPrefixForIntegration(
                $gitlab_repository->getId()
            );

            $gitlab_repositories_representations[] = new GitlabRepositoryRepresentation(
                $gitlab_repository->getId(),
                $gitlab_repository->getGitlabRepositoryId(),
                $gitlab_repository->getName(),
                $gitlab_repository->getDescription(),
                $gitlab_repository->getGitlabRepositoryUrl(),
                $gitlab_repository->getLastPushDate()->getTimestamp(),
                $gitlab_repository->getProject(),
                $gitlab_repository->isArtifactClosureAllowed(),
                $integration_webhook !== null,
                $create_branch_prefix
            );
        }

        return $gitlab_repositories_representations;
    }

    /**
     * @return GitlabRepositoryRepresentation[]
     */
    public function getAllIntegrationsRepresentationsInProjectWithConfiguredToken(Project $project): array
    {
        $all_integrations_representations        = $this->getAllIntegrationsRepresentationsInProject($project);
        $configured_integrations_representations = [];

        foreach ($all_integrations_representations as $representation) {
            if ($representation->is_webhook_configured === false) {
                continue;
            }

            $configured_integrations_representations[] = $representation;
        }

        return $configured_integrations_representations;
    }
}
