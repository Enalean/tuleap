<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Bot;

use Tuleap\DB\DataAccessObject;

class BotDao extends DataAccessObject
{
    /**
     * @psalm-return list<array{id:int, name:string, webhook_url:string, avatar_url:string, project_id:null}>
     */
    public function searchSystemBots(): array
    {
        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE project_id IS NULL";

        return $this->getDB()->run($sql);
    }

    /**
     * @psalm-return list<array{id:int, name:string, webhook_url:string, avatar_url:string, project_id:int}>
     */
    public function searchProjectBots(int $project_id): array
    {
        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE project_id = ?";

        return $this->getDB()->run($sql, $project_id);
    }

    public function addBot(string $bot_name, string $bot_webhook_url, string $bot_avatar_url, ?int $project_id): int
    {
        return $this->getDB()->insertReturnId(
            'plugin_botmattermost_bot',
            [
                'name'        => $bot_name,
                'webhook_url' => $bot_webhook_url,
                'avatar_url'  => $bot_avatar_url,
                'project_id'  => $project_id,
            ]
        );
    }

    public function deleteBot(int $bot_id): bool
    {
        try {
            $this->getDB()->delete(
                'plugin_botmattermost_bot',
                [
                    'id' => $bot_id,
                ]
            );
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function updateBot(string $bot_name, string $bot_webhook_url, string $bot_avatar_url, int $id): bool
    {
        try {
            $this->getDB()->update(
                'plugin_botmattermost_bot',
                [
                    'name'        => $bot_name,
                    'webhook_url' => $bot_webhook_url,
                    'avatar_url'  => $bot_avatar_url,
                ],
                [
                    'id' => $id,
                ]
            );
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function isThereAnotherSystemBotWithNameAndWebhookUrl(int $bot_id, string $bot_name, string $bot_webhook_url): bool
    {
        $sql = "SELECT NULL
                FROM plugin_botmattermost_bot
                WHERE name = ?
                    AND webhook_url = ?
                    AND project_id is NULL
                    AND id <> ?";

        $rows = $this->getDB()->run($sql, $bot_name, $bot_webhook_url, $bot_id);

        return count($rows) > 0;
    }

    public function isASystemBotWithNameAndWebhookUrlAlreadyExisting(string $bot_name, string $bot_webhook_url): bool
    {
        $sql = "SELECT NULL
                FROM plugin_botmattermost_bot
                WHERE name = ?
                    AND webhook_url = ?
                    AND project_id is NULL";

        $rows = $this->getDB()->run($sql, $bot_name, $bot_webhook_url);

        return count($rows) > 0;
    }

    public function isThereAnotherProjectBotWithNameWebhookUrlAndProjectId(int $bot_id, string $bot_name, string $bot_webhook_url, int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_botmattermost_bot
                WHERE name = ?
                    AND webhook_url = ?
                    AND project_id = ?
                    AND id <> ?";

        $rows = $this->getDB()->run($sql, $bot_name, $bot_webhook_url, $project_id, $bot_id);

        return count($rows) > 0;
    }

    public function isAProjectBotWithNameWebhookUrlAndProjectIdAlreadyExisting(string $bot_name, string $bot_webhook_url, int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM plugin_botmattermost_bot
                WHERE name = ?
                    AND webhook_url = ?
                    AND project_id = ?";

        $rows = $this->getDB()->run($sql, $bot_name, $bot_webhook_url, $project_id);

        return count($rows) > 0;
    }

    /**
     * @psalm-return null|array{id:int, name:string, webhook_url:string, avatar_url:string}
     */
    public function searchBotById(int $bot_id): ?array
    {
        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE id = ?";

        return $this->getDB()->row($sql, $bot_id);
    }
}
