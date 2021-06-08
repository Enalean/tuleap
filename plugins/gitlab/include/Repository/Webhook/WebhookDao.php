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

namespace Tuleap\Gitlab\Repository\Webhook;

use Tuleap\DB\DataAccessObject;

class WebhookDao extends DataAccessObject
{
    /**
     * @psalm-return array{integration_id:int, webhook_secret:string, gitlab_webhook_id: int}
     */
    public function getGitlabRepositoryWebhook(int $integration_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_integration_webhook
                WHERE integration_id = ?';

        return $this->getDB()->row($sql, $integration_id);
    }

    public function isIntegrationWebhookUsedByIntegrations(int $gitlab_webhook_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration_webhook
                WHERE gitlab_webhook_id = ?';

        $rows = $this->getDB()->run($sql, $gitlab_webhook_id);

        return count($rows) > 0;
    }

    public function projectHasIntegrationsWithSecretConfigured(int $project_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration
                    LEFT JOIN plugin_gitlab_repository_integration_webhook
                        ON (plugin_gitlab_repository_integration.id = plugin_gitlab_repository_integration_webhook.integration_id)
                WHERE plugin_gitlab_repository_integration_webhook.integration_id IS NOT NULL
                    AND plugin_gitlab_repository_integration.project_id = ?';

        $rows = $this->getDB()->run($sql, $project_id);

        return count($rows) > 0;
    }

    public function deleteGitlabRepositoryWebhook(int $integration_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_webhook',
            ['integration_id' => $integration_id]
        );
    }

    public function deleteAllGitlabRepositoryWebhookConfigurationUsingOldOne(int $gitlab_webhook_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_integration_webhook',
            ['gitlab_webhook_id' => $gitlab_webhook_id]
        );
    }

    public function storeWebhook(int $integration_id, int $webhook_id, string $encrypted_secret): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_integration_webhook',
            [
                'integration_id'    => $integration_id,
                'webhook_secret'    => $encrypted_secret,
                'gitlab_webhook_id' => $webhook_id,
            ],
            [
                'webhook_secret',
                'gitlab_webhook_id',
            ]
        );
    }
}
