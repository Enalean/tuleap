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
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\API\GitlabResponseAPIException;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequest;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
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
    /**
     * @var PostMergeRequestWebhookAuthorDataRetriever
     */
    private $author_data_retriever;
    /**
     * @var GitlabMergeRequestReferenceRetriever
     */
    private $gitlab_merge_request_reference_retriever;

    public function __construct(
        MergeRequestTuleapReferenceDao $merge_request_reference_dao,
        LoggerInterface $logger,
        PostMergeRequestBotCommenter $commenter,
        PreviouslySavedReferencesRetriever $previously_saved_references_retriever,
        CrossReferenceFromMergeRequestCreator $cross_reference_creator,
        PostMergeRequestWebhookAuthorDataRetriever $author_data_retriever,
        GitlabMergeRequestReferenceRetriever $gitlab_merge_request_reference_retriever,
    ) {
        $this->merge_request_reference_dao              = $merge_request_reference_dao;
        $this->logger                                   = $logger;
        $this->commenter                                = $commenter;
        $this->previously_saved_references_retriever    = $previously_saved_references_retriever;
        $this->cross_reference_creator                  = $cross_reference_creator;
        $this->author_data_retriever                    = $author_data_retriever;
        $this->gitlab_merge_request_reference_retriever = $gitlab_merge_request_reference_retriever;
    }

    public function process(GitlabRepositoryIntegration $gitlab_repository_integration, PostMergeRequestWebhookData $webhook_data): void
    {
        $old_references = $this->previously_saved_references_retriever->retrievePreviousReferences(
            $webhook_data,
            $gitlab_repository_integration
        );
        $new_references = $this->cross_reference_creator->createCrossReferencesFromMergeRequest(
            $webhook_data,
            $gitlab_repository_integration,
        );

        $already_save_merge_request = $this->getAlreadySaveMergeRequest(
            $gitlab_repository_integration,
            $webhook_data
        );

        if ($this->shouldWeSaveMergeRequestData($new_references, $already_save_merge_request)) {
            $this->saveMergeRequestData($gitlab_repository_integration, $webhook_data);
        }

        if ($this->shouldWeSaveAuthorData($new_references, $already_save_merge_request)) {
            $this->saveMergeRequestAuthorData($gitlab_repository_integration, $webhook_data);
        }

        if ($this->shouldWeAddCommentOnMergeRequest($old_references, $new_references)) {
            $this->commenter->addCommentOnMergeRequest($webhook_data, $gitlab_repository_integration, $new_references);
        }
    }

    private function saveMergeRequestData(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostMergeRequestWebhookData $webhook_data,
    ): void {
        $merge_request_id = $webhook_data->getMergeRequestId();

        $this->merge_request_reference_dao->saveGitlabMergeRequestInfo(
            $gitlab_repository_integration->getId(),
            $merge_request_id,
            $webhook_data->getTitle(),
            $webhook_data->getDescription(),
            $webhook_data->getSourceBranch(),
            $webhook_data->getState(),
            $webhook_data->getCreatedAtDate()->getTimestamp()
        );

        $this->logger->info("Merge request data for $merge_request_id saved in database");
    }

    private function saveMergeRequestAuthorData(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostMergeRequestWebhookData $webhook_data,
    ): void {
        try {
            $this->logger->info("Try to get author data of merge request #{$webhook_data->getMergeRequestId()}");
            $author_data = $this->author_data_retriever->retrieveAuthorData($gitlab_repository_integration, $webhook_data);

            if ($author_data && isset($author_data['name'])) {
                $author_name  = $author_data['name'];
                $author_email = $author_data['public_email'];

                $this->logger->info("|_ Author name of merge request #{$webhook_data->getMergeRequestId()} is: $author_name");

                $this->merge_request_reference_dao->setAuthorData(
                    $gitlab_repository_integration->getId(),
                    $webhook_data->getMergeRequestId(),
                    $author_name,
                    $author_email
                );

                $this->logger->info("|_ Author has been saved in database");
            }
        } catch (GitlabRequestException | GitlabResponseAPIException $e) {
            $this->logger->error("| |_Can't get data on author of merge request #{$webhook_data->getMergeRequestId()}", ['exception' => $e]);
        }
    }

    private function getAlreadySaveMergeRequest(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostMergeRequestWebhookData $webhook_data,
    ): ?GitlabMergeRequest {
        return $this->gitlab_merge_request_reference_retriever->getGitlabMergeRequestInRepositoryWithId(
            $gitlab_repository_integration,
            $webhook_data->getMergeRequestId()
        );
    }

    /**
     * @param WebhookTuleapReference[] $cross_references
     */
    private function shouldWeSaveAuthorData(
        array $cross_references,
        ?GitlabMergeRequest $already_saved_merge_request,
    ): bool {
        if ($already_saved_merge_request) {
            return ! $already_saved_merge_request->isAuthorAlreadyFetched();
        }

        return ! empty($cross_references);
    }

    private function shouldWeSaveMergeRequestData(
        array $cross_references,
        ?GitlabMergeRequest $already_saved_merge_request,
    ): bool {
        if (! empty($cross_references)) {
            return true;
        }

        return $already_saved_merge_request !== null;
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
