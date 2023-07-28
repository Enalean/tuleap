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

use DateTimeImmutable;
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
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferenceManager;
use Tuleap\Reference\CrossReferencesDao;

class PostPushWebhookActionBranchHandler
{
    public function __construct(
        private readonly BranchNameTuleapReferenceParser $branch_name_tuleap_reference_parser,
        private readonly ReferenceManager $reference_manager,
        private readonly TuleapReferenceRetriever $tuleap_reference_retriever,
        private readonly BranchInfoDao $branch_info_dao,
        private readonly CrossReferencesDao $cross_reference_dao,
        private readonly CrossReferenceManager $cross_reference_manager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function parseBranchReference(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        PostPushWebhookData $webhook_data,
        DateTimeImmutable $webhook_reception_date,
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

            $sha1 = $webhook_data->getCheckoutSha();
            if ($sha1 === null) {
                $this->deleteBranchInformationInIntegration(
                    $gitlab_repository_integration,
                    $branch_name
                );
                return;
            }

            if ($this->cross_reference_dao->existInDb($cross_reference)) {
                $this->updateBranchInformation(
                    $tuleap_reference,
                    $gitlab_repository_integration,
                    $sha1,
                    $branch_name,
                    $webhook_reception_date
                );
                return;
            }

            $this->reference_manager->insertCrossReference($cross_reference);
            $this->saveBranchData(
                $gitlab_repository_integration,
                $sha1,
                $branch_name,
                $webhook_reception_date
            );
        } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function buildCrossReference(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        WebhookTuleapReference $tuleap_reference,
        string $branch_name,
        \Reference $external_reference,
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

    private function updateBranchInformation(
        WebhookTuleapReference $tuleap_reference,
        GitlabRepositoryIntegration $gitlab_repository_integration,
        string $commit_sha1,
        string $branch_name,
        DateTimeImmutable $webhook_reception_date,
    ): void {
        $this->logger->info(
            "|  |_ Tuleap artifact #" . $tuleap_reference->getId() . " already references branch $branch_name. Updating the SHA1 and last push date."
        );

        $this->branch_info_dao->updateGitlabBranchInformation(
            $gitlab_repository_integration->getId(),
            $commit_sha1,
            $branch_name,
            $webhook_reception_date->getTimestamp()
        );

        $this->logger->info("|  |_ SHA1 of branch data for $branch_name updated in database");
    }

    private function saveBranchData(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        string $commit_sha1,
        string $branch_name,
        DateTimeImmutable $webhook_reception_date,
    ): void {
        $this->branch_info_dao->saveGitlabBranchInfo(
            $gitlab_repository_integration->getId(),
            $commit_sha1,
            $branch_name,
            $webhook_reception_date->getTimestamp()
        );

        $this->logger->info("|  |_ Branch data for $branch_name saved in database");
    }

    private function deleteBranchInformationInIntegration(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        string $branch_name,
    ): void {
        $this->logger->info(
            "Branch $branch_name has been deleted, all references will be removed from database for the integration #" . $gitlab_repository_integration->getId()
        );

        $this->cross_reference_manager->deleteEntity(
            $gitlab_repository_integration->getName() . '/' . $branch_name,
            GitlabBranchReference::NATURE_NAME,
            (int) $gitlab_repository_integration->getProject()->getID()
        );

        $this->branch_info_dao->deleteBranchInGitlabIntegration(
            $gitlab_repository_integration->getId(),
            $branch_name
        );
    }
}
