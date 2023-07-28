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

namespace Tuleap\Gitlab\Repository\Webhook\TagPush;

use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Gitlab\API\Tag\GitlabTag;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Reference\CrossReference;

class TagPushWebhookCreateAction
{
    /**
     * @var GitlabTagRetriever
     */
    private $gitlab_tag_retriever;
    /**
     * @var CredentialsRetriever
     */
    private $credentials_retriever;
    /**
     * @var WebhookTuleapReferencesParser
     */
    private $tuleap_references_parser;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var TagInfoDao
     */
    private $tag_info_dao;

    public function __construct(
        CredentialsRetriever $credentials_retriever,
        GitlabTagRetriever $gitlab_tag_retriever,
        WebhookTuleapReferencesParser $tuleap_references_parser,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        ReferenceManager $reference_manager,
        TagInfoDao $tag_info_dao,
        LoggerInterface $logger,
    ) {
        $this->credentials_retriever      = $credentials_retriever;
        $this->gitlab_tag_retriever       = $gitlab_tag_retriever;
        $this->tuleap_references_parser   = $tuleap_references_parser;
        $this->tuleap_reference_retriever = $tuleap_reference_retriever;
        $this->reference_manager          = $reference_manager;
        $this->tag_info_dao               = $tag_info_dao;
        $this->logger                     = $logger;
    }

    public function createTagReferences(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        TagPushWebhookData $tag_push_webhook_data,
    ): void {
        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository_integration);
        if ($credentials === null) {
            //Do nothing, not able to query the GitLab API
            $this->logger->warning("No credentials found for the repository, tag reference cannot be extracted.");
            return;
        }

        $tag_name = $tag_push_webhook_data->getTagName();

        $gitlab_tag_from_api = $this->gitlab_tag_retriever->getTagFromGitlabAPI(
            $credentials,
            $gitlab_repository_integration,
            $tag_name
        );

        $references_collection = $this->tuleap_references_parser->extractCollectionOfTuleapReferences(
            $gitlab_tag_from_api->getMessage()
        );

        $this->logger->info(count($references_collection->getTuleapReferences()) . " Tuleap references found in tag message " . $tag_push_webhook_data->getRef());

        $valid_tuleap_references = [];
        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("|_ Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");

            try {
                $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference($tuleap_reference->getId());

                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added in project the GitLab repository is integrated in."
                );

                $this->saveReferenceInIntegratedProject(
                    $gitlab_repository_integration,
                    $tuleap_reference,
                    $tag_push_webhook_data,
                    $external_reference
                );

                $valid_tuleap_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $reference_exception) {
                $this->logger->error($reference_exception->getMessage());
            }
        }

        if (! empty($valid_tuleap_references)) {
            $this->saveTagData(
                $gitlab_repository_integration,
                $gitlab_tag_from_api
            );
        }
    }

    private function saveReferenceInIntegratedProject(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        WebhookTuleapReference $tuleap_reference,
        TagPushWebhookData $tag_push_webhook_data,
        \Reference $external_reference,
    ): void {
        $cross_reference = new CrossReference(
            $gitlab_repository_integration->getName() . '/' . $tag_push_webhook_data->getTagName(),
            (int) $gitlab_repository_integration->getProject()->getID(),
            GitlabTagReference::NATURE_NAME,
            GitlabTagReference::REFERENCE_NAME,
            $tuleap_reference->getId(),
            $external_reference->getGroupId(),
            $external_reference->getNature(),
            $external_reference->getKeyword(),
            0
        );

        $this->reference_manager->insertCrossReference($cross_reference);
    }

    private function saveTagData(GitlabRepositoryIntegration $gitlab_repository_integration, GitlabTag $gitlab_tag_from_api): void
    {
        $tag_name = $gitlab_tag_from_api->getName();

        $this->tag_info_dao->saveGitlabTagInfo(
            $gitlab_repository_integration->getId(),
            $gitlab_tag_from_api->getCommitSha1(),
            $tag_name,
            $gitlab_tag_from_api->getMessage(),
        );
        $this->logger->info("Tag data for $tag_name saved in database for the integration #" . $gitlab_repository_integration->getId());
    }
}
