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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Branch;

use CrossReference;
use CrossReferenceDao;
use Psr\Log\LoggerInterface;
use ReferenceManager;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\EmptyBranchNameException;
use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushWebhookData;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataBranchNameExtractor;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PostPushWebhookActionBranchHandler
{
    private ReferenceManager $reference_manager;
    private TuleapReferenceRetriever $tuleap_reference_retriever;
    private CrossReferenceDao $cross_reference_dao;
    private LoggerInterface $logger;
    private BranchTuleapReferenceDao $branch_tuleap_reference_dao;
    private BranchNameTuleapReferenceParser $branch_name_tuleap_reference_parser;

    public function __construct(
        BranchNameTuleapReferenceParser $branch_name_tuleap_reference_parser,
        ReferenceManager $reference_manager,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        BranchTuleapReferenceDao $branch_tuleap_reference_dao,
        CrossReferenceDao $cross_reference_dao,
        LoggerInterface $logger
    ) {
        $this->branch_name_tuleap_reference_parser = $branch_name_tuleap_reference_parser;
        $this->reference_manager                   = $reference_manager;
        $this->tuleap_reference_retriever          = $tuleap_reference_retriever;
        $this->branch_tuleap_reference_dao         = $branch_tuleap_reference_dao;
        $this->cross_reference_dao                 = $cross_reference_dao;
        $this->logger                              = $logger;
    }

    public function parseBranchReference(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostPushWebhookData $webhook_data
    ): void {
        try {
            $branch_name = WebhookDataBranchNameExtractor::extractBranchName($webhook_data->getReference());
        } catch (EmptyBranchNameException $exception) {
            $this->logger->error("Branch name is empty.");
            return;
        }

        $tuleap_reference = $this->branch_name_tuleap_reference_parser->extractTuleapReferenceFromBranchName(
            $branch_name
        );

        if ($tuleap_reference === null) {
            $this->logger->info("No Tuleap reference found in branch name " . $branch_name);
            return;
        }

        $this->logger->info("A Tuleap reference found in branch name " . $branch_name);
        $this->logger->info("|_ Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");

        try {
            $external_reference = $this->tuleap_reference_retriever->retrieveTuleapReference($tuleap_reference->getId());

            $this->logger->info(
                "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " found, cross-reference will be added in project the GitLab repository is integrated in."
            );

            $cross_reference = $this->buildCrossReference(
                $gitlab_repository_integration,
                $tuleap_reference,
                $branch_name,
                $external_reference
            );

            if ($this->cross_reference_dao->existInDb($cross_reference)) {
                //We do not add a branch already referenced in artifact
                $this->logger->info(
                    "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " already references branch $branch_name. Skipping."
                );
                return;
            }

            $this->reference_manager->insertCrossReference($cross_reference);
            $this->saveBranchData($gitlab_repository_integration, $webhook_data, $branch_name);
        } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function buildCrossReference(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        WebhookTuleapReference $tuleap_reference,
        string $branch_name,
        \Reference $external_reference
    ): CrossReference {
        return new CrossReference(
            $gitlab_repository_integration->getName() . '/' . $branch_name,
            (int) $gitlab_repository_integration->getProject()->getID(),
            GitlabBranchReference::NATURE_NAME,
            GitlabBranchReference::REFERENCE_NAME,
            $tuleap_reference->getId(),
            $external_reference->getGroupId(),
            $external_reference->getNature(),
            $external_reference->getKeyword(),
            0
        );
    }

    private function saveBranchData(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostPushWebhookData $webhook_data,
        string $branch_name
    ): void {
        $commit_sha1 = $webhook_data->getCheckoutSha();

        $this->branch_tuleap_reference_dao->saveGitlabBranchInfo(
            $gitlab_repository_integration->getId(),
            $commit_sha1,
            $branch_name
        );

        $this->logger->info("|  |_ Branch data for $branch_name saved in database");
    }
}
