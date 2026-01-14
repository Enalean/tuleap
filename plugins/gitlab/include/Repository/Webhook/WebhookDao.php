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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionAdditionalData;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;

class WebhookDao extends DataAccessObject
{
    /**
     * @psalm-return array{integration_id:int, gitlab_webhook_id: int}
     */
    public function getGitlabRepositoryWebhook(int $integration_id): ?array
    {
        $sql = 'SELECT integration_id, gitlab_webhook_id
                FROM plugin_gitlab_repository_integration_webhook
                WHERE integration_id = ?';

        return $this->getDB()->row($sql, $integration_id);
    }

    /**
     * @return Option<ConcealedString>
     */
    public function getGitlabRepositoryWebhookSecret(int $integration_id): Option
    {
        $row = $this->getDB()->row(
            'SELECT webhook_secret FROM plugin_gitlab_repository_integration_webhook WHERE integration_id = ?',
            $integration_id
        );

        if ($row === null) {
            return Option::nothing(ConcealedString::class);
        }

        $secret = $this->decryptDataStoredInATableRow(
            $row['webhook_secret'],
            $this->getWebhookSecretEncryptionAdditionalData($integration_id)
        );
        \sodium_memzero($row['webhook_secret']);

        return Option::fromValue($secret);
    }

    public function isIntegrationWebhookUsedByIntegrations(int $gitlab_webhook_id): bool
    {
        $sql = 'SELECT NULL
                FROM plugin_gitlab_repository_integration_webhook
                WHERE gitlab_webhook_id = ?';

        $rows = $this->getDB()->run($sql, $gitlab_webhook_id);

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

    public function storeWebhook(int $integration_id, int $webhook_id, ConcealedString $secret): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_integration_webhook',
            [
                'integration_id'    => $integration_id,
                'webhook_secret'    => $this->encryptDataToStoreInATableRow($secret, $this->getWebhookSecretEncryptionAdditionalData($integration_id)),
                'gitlab_webhook_id' => $webhook_id,
            ],
            [
                'webhook_secret',
                'gitlab_webhook_id',
            ]
        );
    }

    private function getWebhookSecretEncryptionAdditionalData(int $integration_id): EncryptionAdditionalData
    {
        return new EncryptionAdditionalData('plugin_gitlab_repository_integration_webhook', 'webhook_secret', (string) $integration_id);
    }
}
