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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\PostPushWebhookActionBranchHandler;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferenceDao;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;
use Tuleap\Reference\CrossReference;
use UserNotExistException;

class PostPushWebhookActionProcessor
{
    /**
     * @var WebhookTuleapReferencesParser
     */
    private $commit_tuleap_references_parser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ReferenceManager
     */
    private $reference_manager;

    /**
     * @var CommitTuleapReferenceDao
     */
    private $commit_tuleap_reference_dao;

    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var PostPushCommitBotCommenter
     */
    private $commenter;
    /**
     * @var PostPushWebhookCloseArtifactHandler
     */
    private $close_artifact_handler;

    private PostPushWebhookActionBranchHandler $action_branch_handler;

    public function __construct(
        WebhookTuleapReferencesParser $commit_tuleap_references_parser,
        CommitTuleapReferenceDao $commit_tuleap_reference_dao,
        ReferenceManager $reference_manager,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        LoggerInterface $logger,
        PostPushCommitBotCommenter $commenter,
        PostPushWebhookCloseArtifactHandler $close_artifact_handler,
        PostPushWebhookActionBranchHandler $action_branch_handler,
    ) {
        $this->commit_tuleap_references_parser = $commit_tuleap_references_parser;
        $this->commit_tuleap_reference_dao     = $commit_tuleap_reference_dao;
        $this->reference_manager               = $reference_manager;
        $this->tuleap_reference_retriever      = $tuleap_reference_retriever;
        $this->logger                          = $logger;
        $this->commenter                       = $commenter;
        $this->close_artifact_handler          = $close_artifact_handler;
        $this->action_branch_handler           = $action_branch_handler;
    }

    /**
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     */
    public function process(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostPushWebhookData $webhook_data,
        DateTimeImmutable $webhook_reception_date,
    ): void {
        foreach ($webhook_data->getCommits() as $commit_webhook_data) {
            $this->parseCommitReferences($gitlab_repository_integration, $commit_webhook_data);
        }

        $this->action_branch_handler->parseBranchReference(
            $gitlab_repository_integration,
            $webhook_data,
            $webhook_reception_date
        );
    }

    /**
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    private function parseCommitReferences(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostPushCommitWebhookData $commit_webhook_data,
    ): void {
        $references_collection = $this->commit_tuleap_references_parser->extractCollectionOfTuleapReferences(
            $commit_webhook_data->getMessage()
        );

        $good_references = [];

        $this->logger->info(count($references_collection->getTuleapReferences()) . " Tuleap references found in commit " . $commit_webhook_data->getSha1());

        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("|_ Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");

            try {
                $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference($tuleap_reference->getId());

                assert($external_reference instanceof \Reference);

                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added in project the GitLab repository is integrated in."
                );

                $this->saveCommitReferenceInIntegratedProject(
                    $gitlab_repository_integration,
                    $tuleap_reference,
                    $commit_webhook_data,
                    $external_reference,
                );

                $this->close_artifact_handler->handleArtifactClosure(
                    $tuleap_reference,
                    $commit_webhook_data,
                    $gitlab_repository_integration
                );

                $good_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException | UserNotExistException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        if (! empty($good_references)) {
            // Save commit data if there is at least 1 good artifact reference in the commit message
            $this->saveCommitData($gitlab_repository_integration, $commit_webhook_data);
            $this->commenter->addCommentOnCommit($commit_webhook_data, $gitlab_repository_integration, $good_references);
        }
    }

    private function saveCommitReferenceInIntegratedProject(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        WebhookTuleapReference $tuleap_reference,
        PostPushCommitWebhookData $commit_webhook_data,
        \Reference $external_reference,
    ): void {
        $cross_reference = new CrossReference(
            $gitlab_repository_integration->getName() . '/' . $commit_webhook_data->getSha1(),
            (int) $gitlab_repository_integration->getProject()->getID(),
            GitlabCommitReference::NATURE_NAME,
            GitlabCommitReference::REFERENCE_NAME,
            $tuleap_reference->getId(),
            $external_reference->getGroupId(),
            $external_reference->getNature(),
            $external_reference->getKeyword(),
            0
        );

        $this->reference_manager->insertCrossReference($cross_reference);
    }

    private function saveCommitData(GitlabRepositoryIntegration $gitlab_repository_integration, PostPushCommitWebhookData $commit_webhook_data): void
    {
        $commit_sha1 = $commit_webhook_data->getSha1();
        $this->commit_tuleap_reference_dao->saveGitlabCommitInfo(
            $gitlab_repository_integration->getId(),
            $commit_sha1,
            $commit_webhook_data->getCommitDate(),
            $commit_webhook_data->getTitle(),
            $commit_webhook_data->getBranchName(),
            $commit_webhook_data->getAuthorName(),
            $commit_webhook_data->getAuthorEmail()
        );
        $this->logger->info("Commit data for $commit_sha1 saved in database");
    }
}
