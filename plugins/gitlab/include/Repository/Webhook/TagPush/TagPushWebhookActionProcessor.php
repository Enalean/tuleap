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

use CrossReference;
use Project;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Gitlab\API\Tag\GitlabTag;
use Tuleap\Gitlab\API\Tag\GitlabTagRetriever;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Webhook\Bot\CredentialsRetriever;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class TagPushWebhookActionProcessor
{
    private const NO_REFERENCE = '0000000000000000000000000000000000000000';

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
     * @var GitlabRepositoryProjectRetriever
     */
    private $gitlab_repository_project_retriever;
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
    /**
     * @var TagPushWebhookDeleteAction
     */
    private $push_webhook_delete_action;

    public function __construct(
        CredentialsRetriever $credentials_retriever,
        GitlabTagRetriever $gitlab_tag_retriever,
        WebhookTuleapReferencesParser $tuleap_references_parser,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        GitlabRepositoryProjectRetriever $gitlab_repository_project_retriever,
        ReferenceManager $reference_manager,
        TagInfoDao $tag_info_dao,
        TagPushWebhookDeleteAction $push_webhook_delete_action,
        LoggerInterface $logger
    ) {
        $this->credentials_retriever               = $credentials_retriever;
        $this->gitlab_tag_retriever                = $gitlab_tag_retriever;
        $this->tuleap_references_parser            = $tuleap_references_parser;
        $this->tuleap_reference_retriever          = $tuleap_reference_retriever;
        $this->gitlab_repository_project_retriever = $gitlab_repository_project_retriever;
        $this->reference_manager                   = $reference_manager;
        $this->tag_info_dao                        = $tag_info_dao;
        $this->push_webhook_delete_action          = $push_webhook_delete_action;
        $this->logger                              = $logger;
    }

    public function process(GitlabRepository $gitlab_repository, TagPushWebhookData $tag_push_webhook_data): void
    {
        if ($tag_push_webhook_data->getAfter() === self::NO_REFERENCE) {
            $this->push_webhook_delete_action->deleteTagReferences(
                $gitlab_repository,
                $tag_push_webhook_data
            );
            return;
        }

        $credentials = $this->credentials_retriever->getCredentials($gitlab_repository);
        if ($credentials === null) {
            //Do nothing, not able to query the GitLab API
            $this->logger->warning("No credentials found for the repository, tag reference cannot be extracted.");
            return;
        }

        $tag_name = $tag_push_webhook_data->getTagName();

        $projects = $this->gitlab_repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn(
            $gitlab_repository
        );

        $gitlab_tag = $this->gitlab_tag_retriever->getTagFromGitlabAPI(
            $credentials,
            $gitlab_repository,
            $tag_name
        );

        $references_collection = $this->tuleap_references_parser->extractCollectionOfTuleapReferences(
            $gitlab_tag->getMessage()
        );

        $this->logger->info(count($references_collection->getTuleapReferences()) . " Tuleap references found in tag message " . $tag_push_webhook_data->getRef());

        $valid_tuleap_references = [];
        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("|_ Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");

            try {
                $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference($tuleap_reference->getId());

                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added for each project the GitLab repository is integrated in."
                );

                $this->saveReferenceInEachIntegratedProject(
                    $gitlab_repository,
                    $tuleap_reference,
                    $tag_push_webhook_data,
                    $external_reference,
                    $projects
                );

                $valid_tuleap_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $reference_exception) {
                $this->logger->error($reference_exception->getMessage());
            }
        }

        if (! empty($valid_tuleap_references)) {
            $this->saveTagData(
                $gitlab_repository,
                $gitlab_tag
            );
        }
    }

    /**
     * @param Project[] $projects
     */
    private function saveReferenceInEachIntegratedProject(
        GitlabRepository $gitlab_repository,
        WebhookTuleapReference $tuleap_reference,
        TagPushWebhookData $tag_push_webhook_data,
        \Reference $external_reference,
        array $projects
    ): void {
        foreach ($projects as $project) {
            $cross_reference = new CrossReference(
                $gitlab_repository->getName() . '/' . $tag_push_webhook_data->getTagName(),
                $project->getID(),
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
    }

    private function saveTagData(GitlabRepository $gitlab_repository, GitlabTag $gitlab_tag): void
    {
        $tag_name = $gitlab_tag->getName();

        $this->tag_info_dao->saveGitlabTagInfo(
            $gitlab_repository->getId(),
            $gitlab_tag->getCommitSha1(),
            $tag_name,
            $gitlab_tag->getMessage(),
        );
        $this->logger->info("Tag data for $tag_name saved in database");
    }
}
