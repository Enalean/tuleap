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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Reference\CrossReference;

class CrossReferenceFromMergeRequestCreator
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
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var TuleapReferencesFromMergeRequestDataExtractor
     */
    private $references_from_merge_request_data_extractor;

    public function __construct(
        TuleapReferencesFromMergeRequestDataExtractor $references_from_merge_request_data_extractor,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        \ReferenceManager $reference_manager,
        LoggerInterface $logger,
    ) {
        $this->references_from_merge_request_data_extractor = $references_from_merge_request_data_extractor;
        $this->tuleap_reference_retriever                   = $tuleap_reference_retriever;
        $this->reference_manager                            = $reference_manager;
        $this->logger                                       = $logger;
    }

    /**
     * @return WebhookTuleapReference[]
     */
    public function createCrossReferencesFromMergeRequest(
        PostMergeRequestWebhookData $webhook_data,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): array {
        $references_collection = $this->references_from_merge_request_data_extractor->extract(
            $webhook_data->getTitle(),
            $webhook_data->getDescription(),
            $webhook_data->getSourceBranch(),
        );

        $good_references = [];

        $nb_found_references = count($references_collection->getTuleapReferences());

        $this->logger->info(
            $nb_found_references . " Tuleap references found in merge request " . $webhook_data->getMergeRequestId()
        );

        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info(
                "|_ Reference to Tuleap artifact #{$tuleap_reference->getId()} found, cross-reference will be added in project the GitLab repository is integrated in."
            );

            try {
                $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference(
                    $tuleap_reference->getId()
                );

                assert($external_reference instanceof \Reference);

                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found"
                );

                $this->saveReferenceInIntegratedProject(
                    $gitlab_repository_integration,
                    $tuleap_reference,
                    $webhook_data,
                    $external_reference,
                );

                $good_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $reference_exception) {
                $this->logger->error($reference_exception->getMessage());
            }
        }

        return $good_references;
    }

    private function saveReferenceInIntegratedProject(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        WebhookTuleapReference $tuleap_reference,
        PostMergeRequestWebhookData $merge_request_webhook_data,
        \Reference $external_reference,
    ): void {
        $cross_reference = new CrossReference(
            $this->getGitlabMergeRequestReferenceId($gitlab_repository_integration, $merge_request_webhook_data),
            (int) $gitlab_repository_integration->getProject()->getID(),
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

    private function getGitlabMergeRequestReferenceId(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostMergeRequestWebhookData $merge_request_webhook_data,
    ): string {
        return $gitlab_repository_integration->getName() . '/' . $merge_request_webhook_data->getMergeRequestId();
    }
}
