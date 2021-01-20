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

use CrossReference;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class PostMergeRequestWebhookActionProcessor
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var WebhookTuleapReferencesParser
     */
    private $reference_parser;
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
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

    public function __construct(
        WebhookTuleapReferencesParser $reference_parser,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        \ReferenceManager $reference_manager,
        MergeRequestTuleapReferenceDao $merge_request_reference_dao,
        GitlabRepositoryProjectRetriever $gitlab_repository_project_retriever,
        LoggerInterface $logger,
        PostMergeRequestBotCommenter $commenter
    ) {
        $this->reference_parser                    = $reference_parser;
        $this->tuleap_reference_retriever          = $tuleap_reference_retriever;
        $this->reference_manager                   = $reference_manager;
        $this->merge_request_reference_dao         = $merge_request_reference_dao;
        $this->gitlab_repository_project_retriever = $gitlab_repository_project_retriever;
        $this->logger                              = $logger;
        $this->commenter                           = $commenter;
    }

    public function process(GitlabRepository $gitlab_repository, PostMergeRequestWebhookData $webhook_data): void
    {
        $references_collection = $this->reference_parser->extractCollectionOfTuleapReferences(
            $webhook_data->getTitle() . " " . $webhook_data->getDescription()
        );

        $projects = $this->gitlab_repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn(
            $gitlab_repository
        );

        $good_references = [];

        $nb_found_references = count($references_collection->getTuleapReferences());

        $this->logger->info($nb_found_references . " Tuleap references found in merge request " . $webhook_data->getMergeRequestId());

        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("|_ Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added for each project the GitLab repository is integrated in.");

            try {
                $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference($tuleap_reference->getId());

                assert($external_reference instanceof \Reference);

                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found"
                );

                $this->saveReferenceInEachIntegratedProject(
                    $gitlab_repository,
                    $tuleap_reference,
                    $webhook_data,
                    $external_reference,
                    $projects
                );

                $good_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $reference_exception) {
                $this->logger->error($reference_exception->getMessage());
            }
        }

        if (! empty($good_references)) {
            // Save merge request data if there is at least 1 good artifact reference in the merge request description and title
            $this->saveMergeRequestData($gitlab_repository, $webhook_data);
            $this->commenter->addCommentOnMergeRequest($webhook_data, $gitlab_repository, $good_references);
        }
    }

    /**
     * @param Project[] $projects
     */
    private function saveReferenceInEachIntegratedProject(
        GitlabRepository $gitlab_repository,
        WebhookTuleapReference $tuleap_reference,
        PostMergeRequestWebhookData $merge_request_webhook_data,
        \Reference $external_reference,
        array $projects
    ): void {
        foreach ($projects as $project) {
            $cross_reference = new CrossReference(
                $this->getGitlabMergeRequestReferenceId($gitlab_repository, $merge_request_webhook_data),
                $project->getID(),
                GitlabMergeRequestReference::NATURE_NAME,
                GitlabMergeRequestReference::REFERENCE_NAME,
                $tuleap_reference->getId(),
                $external_reference->getGroupId(),
                $external_reference->getNature(),
                $external_reference->getKeyword(),
                0
            );

            $this->reference_manager->insertCrossReference($cross_reference);
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
        );

        $this->logger->info("Merge request data for $merge_request_id saved in database");
    }

    private function getGitlabMergeRequestReferenceId(
        GitlabRepository $gitlab_repository,
        PostMergeRequestWebhookData $merge_request_webhook_data
    ): string {
        return $gitlab_repository->getName() . '/' . $merge_request_webhook_data->getMergeRequestId();
    }
}
