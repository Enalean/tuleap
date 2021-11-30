<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;

class GitlabBranchInformationRetriever
{
    private GitlabRepositoryIntegrationFactory $repository_integration_factory;
    private GitlabProjectBuilder $gitlab_project_builder;
    private CredentialsRetriever $credentials_retriever;

    public function __construct(
        GitlabRepositoryIntegrationFactory $repository_integration_factory,
        CredentialsRetriever $credentials_retriever,
        GitlabProjectBuilder $gitlab_project_builder,
    ) {
        $this->repository_integration_factory = $repository_integration_factory;
        $this->credentials_retriever          = $credentials_retriever;
        $this->gitlab_project_builder         = $gitlab_project_builder;
    }

    /**
     * @throws RestException 404
     */
    public function getBranchInformation(\PFUser $current_user, int $id): BranchesInformationRepresentation
    {
        $gitlab_repository_integration = $this->repository_integration_factory->getIntegrationById($id);

        if ($gitlab_repository_integration === null || ! $current_user->isMember($gitlab_repository_integration->getProject()->getID())) {
            throw new RestException(404, "Repository #$id not found or information not available");
        }

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository_integration);
        if ($credentials === null) {
            throw new RestException(404, "No credentials found for repository #$id");
        }

        try {
            $gitlab_project = $this->gitlab_project_builder->getProjectFromGitlabAPI(
                $credentials,
                $gitlab_repository_integration->getGitlabRepositoryId()
            );
        } catch (GitlabRequestException | GitlabResponseAPIException $e) {
            throw new RestException(404, "Cannot find or access the GitLab project associated to the repository #$id", [], $e);
        }

        return BranchesInformationRepresentation::fromGitLabProject($gitlab_project);
    }
}
