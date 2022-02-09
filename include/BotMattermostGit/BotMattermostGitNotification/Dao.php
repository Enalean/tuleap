<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit\BotMattermostGitNotification;

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{
    /**
     * @psalm-return array{id:int, repository_id:int, bot_id:int}|null
     */
    public function searchBotNotification(int $repository_id): ?array
    {
        $sql = "SELECT *
                FROM plugin_botmattermost_git_notification
                WHERE repository_id = ?";

        return $this->getDB()->row($sql, $repository_id);
    }

    /**
     * @psalm-return list<array{notification_id:int, channel_name:string}>
     */
    public function searchChannels(int $notification_id): array
    {
        $sql = "SELECT *
                FROM plugin_botmattermost_git_notification_channel
                WHERE notification_id = ?";

        return $this->getDB()->run($sql, $notification_id);
    }

    public function createNotification(array $channels, int $bot_id, int $repository_id): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($channels, $bot_id, $repository_id): bool {
                $notification_id = $this->createBotNotification($bot_id, $repository_id);
                $this->createChannels($channels, $notification_id);

                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    public function updateNotification(array $channels, int $repository_id): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($channels, $repository_id): bool {
                $notification_id = $this->getNotificationId($repository_id);
                $this->updateChannels($channels, $notification_id);

                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    public function deleteNotification(int $repository_id): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($repository_id): bool {
                $notification_id = $this->getNotificationId($repository_id);
                $this->deleteBotNotification($notification_id);
                $this->deleteChannels($notification_id);
                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    private function getNotificationId($repository_id): int
    {
        $res = $this->searchNotificationId($repository_id);

        return $res['id'];
    }

    private function searchNotificationId(int $repository_id): ?array
    {
        $sql = "SELECT id
                FROM plugin_botmattermost_git_notification
                WHERE repository_id = ?";

        return $this->getDB()->row($sql, $repository_id);
    }

    private function createBotNotification(int $repository_id, int $bot_id): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_botmattermost_git_notification',
            [
                'repository_id' => $repository_id,
                'bot_id'        => $bot_id,
            ]
        );
    }

    private function createChannels(array $channels, int $notification_id): void
    {
        $data_to_insert = [];
        foreach ($channels as $channel_name) {
            $data_to_insert[] = ['notification_id' => $notification_id, 'channel_name' => $channel_name];
        }

        $this->getDB()->insertMany(
            'plugin_botmattermost_git_notification_channel',
            $data_to_insert
        );
    }

    private function updateChannels(array $channels, int $notification_id): void
    {
        $this->deleteChannels($notification_id);
        if ($this->hasValue($channels)) {
            $this->createChannels($channels, $notification_id);
        }
    }

    private function deleteBotNotification(int $id): void
    {
        $this->getDB()->delete(
            'plugin_botmattermost_git_notification',
            [
                'id' => $id,
            ]
        );
    }

    private function deleteChannels(int $notification_id): void
    {
        $this->getDB()->delete(
            'plugin_botmattermost_git_notification_channel',
            [
                'notification_id' => $notification_id,
            ]
        );
    }

    private function hasValue(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return (trim(implode('', $array)) !== '');
    }

    public function deleteNotificationByBot(Bot $bot): bool
    {
        try {
            $bot_id = $bot->getId();

            $sql = "DELETE notification, channel
                FROM plugin_botmattermost_git_notification AS notification
                INNER JOIN plugin_botmattermost_git_notification_channel AS channel
                  ON notification.id = channel.notification_id
                WHERE bot_id = ?";

            $this->getDB()->run($sql, $bot_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }
}
