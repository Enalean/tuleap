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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\GitlabProjectBuilder;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Artifact\MergeRequestTitleCreatorFromArtifact;
use Tuleap\Gitlab\Plugin\GitlabIntegrationAvailabilityChecker;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegrationFactory;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Tracker\Artifact\RetrieveViewableArtifact;

class GitlabMergeRequestCreator
{
    public function __construct(
        private RetrieveViewableArtifact $artifact_factory,
        private GitlabIntegrationAvailabilityChecker $availability_checker,
        private GitlabRepositoryIntegrationFactory $integration_factory,
        private CredentialsRetriever $credentials_retriever,
        private GitlabProjectBuilder $gitlab_project_builder,
        private ClientWrapper $gitlab_api_client,
        private MergeRequestTitleCreatorFromArtifact $merge_request_title_creator_from_artifact,
    ) {
    }

    /**
     * @throws RestException
     */
    public function createMergeRequestInGitlab(
        PFUser $current_user,
        GitlabMergeRequestPOSTRepresentation $gitlab_merge_request,
    ): void {
        $artifact = $this->artifact_factory->getArtifactByIdUserCanView(
            $current_user,
            $gitlab_merge_request->artifact_id
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
            $gitlab_merge_request->gitlab_integration_id
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

        try {
            $gitlab_project = $this->gitlab_project_builder->getProjectFromGitlabAPI(
                $credentials,
                $integration->getGitlabRepositoryId()
            );
        } catch (GitlabRequestException | GitlabResponseAPIException $e) {
            throw new RestException(404, "Cannot find or access the GitLab project associated to the repository #$gitlab_merge_request->gitlab_integration_id", [], $e);
        }

        $default_branch_name = $gitlab_project->getDefaultBranch();
        $merge_request_title = $this->merge_request_title_creator_from_artifact->getMergeRequestTitle($artifact);

        try {
            $url = "/projects/" . urlencode((string) $integration->getGitlabRepositoryId()) . "/merge_requests?id=" . urlencode((string) $gitlab_merge_request->gitlab_integration_id)
                . "&source_branch=" . urlencode($gitlab_merge_request->source_branch) . "&target_branch=" . urlencode($default_branch_name)
                . "&title=" . urlencode($merge_request_title);

            $this->gitlab_api_client->postUrl(
                $credentials,
                $url,
                []
            );
        } catch (GitlabRequestException $exception) {
            throw new RestException(
                $exception->getErrorCode(),
                "An error occurred while creating the merge request on GitLab: " . $exception->getMessage()
            );
        } catch (GitlabResponseAPIException $exception) {
            throw new RestException(500, $exception->getMessage());
        }
    }
}
