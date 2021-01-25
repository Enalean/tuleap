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
     * @psalm-return array{repository_id:int, webhook_secret:string, gitlab_webhook_id: int}
     */
    public function getGitlabRepositoryWebhook(int $repository_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_gitlab_repository_webhook_secret
                WHERE repository_id = ?';

        return $this->getDB()->row($sql, $repository_id);
    }

    public function deleteGitlabRepositoryWebhook(int $repository_id): void
    {
        $this->getDB()->delete(
            'plugin_gitlab_repository_webhook_secret',
            ['repository_id' => $repository_id]
        );
    }

    public function storeWebhook(int $repository_id, int $webhook_id, string $encrypted_secret): void
    {
        $this->getDB()->insertOnDuplicateKeyUpdate(
            'plugin_gitlab_repository_webhook_secret',
            [
                'repository_id'     => $repository_id,
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
