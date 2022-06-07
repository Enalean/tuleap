<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Artifact\BranchNameCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\REST\I18NRestException;

class GitlabBranchCreator
{
    private Tracker_ArtifactFactory $artifact_factory;
    private GitlabIntegrationAvailabilityChecker $availability_checker;
    private GitlabRepositoryIntegrationFactory $integration_factory;
    private CredentialsRetriever $credentials_retriever;
    private ClientWrapper $gitlab_api_client;
    private BranchNameCreatorFromArtifact $branch_name_creator_from_artifact;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        GitlabIntegrationAvailabilityChecker $availability_checker,
        GitlabRepositoryIntegrationFactory $integration_factory,
        CredentialsRetriever $credentials_retriever,
        ClientWrapper $gitlab_api_client,
        BranchNameCreatorFromArtifact $branch_name_creator_from_artifact,
    ) {
        $this->artifact_factory                  = $artifact_factory;
        $this->availability_checker              = $availability_checker;
        $this->integration_factory               = $integration_factory;
        $this->credentials_retriever             = $credentials_retriever;
        $this->gitlab_api_client                 = $gitlab_api_client;
        $this->branch_name_creator_from_artifact = $branch_name_creator_from_artifact;
    }

    /**
     * @throws RestException
     */
    public function createBranchInGitlab(
        PFUser $current_user,
        GitlabBranchPOSTRepresentation $gitlab_branch,
    ): GitlabBranchRepresentation {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView(
            $current_user,
            $gitlab_branch->artifact_id
        );

        if ($artifact === null) {
            throw new RestException(404, "Artifact not found");
        }

        $project    = $artifact->getTracker()->getProject();
        $project_id = (int) $project->getID();

        if (! $this->availability_checker->isGitlabIntegrationAvailableForProject($project)) {
            throw new RestException(400);
        }

        if (! $current_user->isMember($project_id)) {
            throw new RestException(400);
        }

        $integration = $this->integration_factory->getIntegrationById(
            $gitlab_branch->gitlab_integration_id
        );

        if ($integration === null) {
            throw new RestException(404);
        }

        if (
            (int) $integration->getProject()->getID() !== (int) $artifact->getTracker()->getGroupId()
        ) {
            throw new RestException(400, "GitLab integration and artifact must be in the same project");
        }

        $credentials = $this->credentials_retriever->getCredentials($integration);
        if ($credentials === null) {
            throw new RestException(400, "Integration is not configured");
        }

        $branch_name = $this->branch_name_creator_from_artifact->getFullBranchName($artifact, $integration);
        try {
            $url = "/projects/{$integration->getGitlabRepositoryId()}/repository/branches?branch=" .
                urlencode($branch_name) . "&ref=" . urlencode($gitlab_branch->reference);

            $this->gitlab_api_client->postUrl(
                $credentials,
                $url,
                []
            );
        } catch (GitlabRequestException $exception) {
            if (stripos($exception->getMessage(), "invalid reference name")) {
                throw new I18NRestException(
                    $exception->getErrorCode(),
                    sprintf(dgettext('tuleap-gitlab', "The reference %s does not seem to exist on %s "), $gitlab_branch->reference, $integration->getName())
                );
            }

            if (stripos($exception->getMessage(), "branch already exists")) {
                throw new I18NRestException(
                    $exception->getErrorCode(),
                    sprintf(dgettext('tuleap-gitlab', "The branch %s already exists on %s "), $branch_name, $integration->getName())
                );
            }

            throw new RestException(
                $exception->getErrorCode(),
                sprintf(dgettext('tuleap-gitlab', "An error occurred while creating the branch on GitLab: %s"), $exception->getMessage())
            );
        } catch (GitlabResponseAPIException $exception) {
            throw new RestException(500, $exception->getMessage());
        }

        return GitlabBranchRepresentation::build($branch_name);
    }
}
