<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Project;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Gitlab\Reference\UpdateCrossReference;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\UpdateGitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookData;

final readonly class GitlabRepositoryUpdater
{
    public function __construct(
        private LoggerInterface $logger,
        private DBTransactionExecutor $transaction_executor,
        private UpdateGitlabRepositoryIntegration $repository_integration_updater,
        private UpdateCrossReference $cross_reference_updater,
    ) {
    }

    public function updateRepositoryDataIfNeeded(GitlabRepositoryIntegration $integration, WebhookData $webhook_data): void
    {
        $this->logger->debug(sprintf(
            "Current integration:\n- %s\n- #%d\n- %s\n- %s",
            $integration->getName(),
            $integration->getGitlabRepositoryId(),
            $integration->getGitlabRepositoryUrl(),
            $integration->getDescription(),
        ));
        $this->logger->debug(sprintf(
            "Received repository:\n- %s\n- #%d\n- %s\n- %s",
            $webhook_data->getGitlabProjectName(),
            $webhook_data->getGitlabProjectId(),
            $webhook_data->getGitlabWebUrl(),
            $webhook_data->getGitlabProjectDescription(),
        ));

        if ($integration->getGitlabRepositoryId() !== $webhook_data->getGitlabProjectId()) {
            $this->logger->error('Compared repositories do NOT have the same id!');
            return;
        }

        if (
            $integration->getName() === $webhook_data->getGitlabProjectName() &&
            $integration->getGitlabRepositoryUrl() === $webhook_data->getGitlabWebUrl() &&
            $integration->getDescription() === $webhook_data->getGitlabProjectDescription()
        ) {
            $this->logger->debug('There are NO differences');
            return;
        }
        $this->logger->debug('There are some differences: need update');

        $this->transaction_executor->execute(function () use ($integration, $webhook_data) {
            $this->repository_integration_updater->updateGitlabRepositoryIntegration(
                $integration->getId(),
                $webhook_data->getGitlabProjectName(),
                $webhook_data->getGitlabProjectDescription(),
                $webhook_data->getGitlabWebUrl(),
            );
            $this->updateCrossReferences(
                $integration->getId(),
                $integration->getName(),
                $webhook_data->getGitlabProjectName(),
            );
        });
        $this->logger->debug('Integration updated!');
    }

    private function updateCrossReferences(int $integration_id, string $old_name, string $new_name): void
    {
        $this->cross_reference_updater->updateBranchCrossReference($integration_id, $old_name, $new_name);
        $this->cross_reference_updater->updateCommitCrossReference($integration_id, $old_name, $new_name);
        $this->cross_reference_updater->updateMergeRequestCrossReference($integration_id, $old_name, $new_name);
        $this->cross_reference_updater->updateTagCrossReference($integration_id, $old_name, $new_name);
    }
}
