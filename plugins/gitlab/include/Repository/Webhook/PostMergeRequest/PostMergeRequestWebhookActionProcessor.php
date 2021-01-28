<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PostMergeRequestWebhookActionProcessor
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var MergeRequestTuleapReferenceDao
     */
    private $merge_request_reference_dao;
    /**
     * @var GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;
    /**
     * @var PostMergeRequestBotCommenter
     */
    private $commenter;
    /**
     * @var PreviouslySavedReferencesRetriever
     */
    private $previously_saved_references_retriever;
    /**
     * @var CrossReferenceFromMergeRequestCreator
     */
    private $cross_reference_creator;

    public function __construct(
        MergeRequestTuleapReferenceDao $merge_request_reference_dao,
        GitlabRepositoryProjectRetriever $gitlab_repository_project_retriever,
        LoggerInterface $logger,
        PostMergeRequestBotCommenter $commenter,
        PreviouslySavedReferencesRetriever $previously_saved_references_retriever,
        CrossReferenceFromMergeRequestCreator $cross_reference_creator
    ) {
        $this->merge_request_reference_dao           = $merge_request_reference_dao;
        $this->gitlab_repository_project_retriever   = $gitlab_repository_project_retriever;
        $this->logger                                = $logger;
        $this->commenter                             = $commenter;
        $this->previously_saved_references_retriever = $previously_saved_references_retriever;
        $this->cross_reference_creator               = $cross_reference_creator;
    }

    public function process(GitlabRepository $gitlab_repository, PostMergeRequestWebhookData $webhook_data): void
    {
        $projects = $this->gitlab_repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn(
            $gitlab_repository
        );

        $old_references = $this->previously_saved_references_retriever->retrievePreviousReferences(
            $webhook_data,
            $gitlab_repository
        );
        $new_references = $this->cross_reference_creator->createCrossReferencesFromMergeRequest(
            $webhook_data,
            $gitlab_repository,
            $projects
        );

        if ($this->shouldWeSaveMergeRequestData($new_references, $gitlab_repository, $webhook_data)) {
            $this->saveMergeRequestData($gitlab_repository, $webhook_data);
        }

        if ($this->shouldWeAddCommentOnMergeRequest($old_references, $new_references)) {
            $this->commenter->addCommentOnMergeRequest($webhook_data, $gitlab_repository, $new_references);
        }
    }

    private function saveMergeRequestData(
        GitlabRepository $gitlab_repository,
        PostMergeRequestWebhookData $webhook_data
    ): void {
        $merge_request_id = $webhook_data->getMergeRequestId();

        $this->merge_request_reference_dao->saveGitlabMergeRequestInfo(
            $gitlab_repository->getId(),
            $merge_request_id,
            $webhook_data->getTitle(),
            $webhook_data->getDescription(),
            $webhook_data->getState(),
            $webhook_data->getCreatedAtDate()->getTimestamp()
        );

        $this->logger->info("Merge request data for $merge_request_id saved in database");
    }

    /**
     * @param WebhookTuleapReference[] $cross_references
     */
    private function shouldWeSaveMergeRequestData(
        array $cross_references,
        GitlabRepository $gitlab_repository,
        PostMergeRequestWebhookData $webhook_data
    ): bool {
        if (! empty($cross_references)) {
            return true;
        }

        $already_saved_merge_request_row = $this->merge_request_reference_dao->searchMergeRequestInRepositoryWithId(
            $gitlab_repository->getId(),
            $webhook_data->getMergeRequestId()
        );

        $is_merge_request_already_saved = ! empty($already_saved_merge_request_row);

        return $is_merge_request_already_saved;
    }

    /**
     * @param WebhookTuleapReference[] $old_references
     * @param WebhookTuleapReference[] $new_references
     *
     */
    private function shouldWeAddCommentOnMergeRequest(array $old_references, array $new_references): bool
    {
        if (empty($new_references)) {
            return false;
        }

        $are_references_removed = ! empty(array_diff($old_references, $new_references));
        if ($are_references_removed) {
            $this->logger->debug('Some references are removed, a comment should be added');

            return true;
        }

        $are_references_added = ! empty(array_diff($new_references, $old_references));
        if ($are_references_added) {
            $this->logger->debug('Some references are added, a comment should be added');
        }

        return $are_references_added;
    }
}
