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

namespace Tuleap\BotMattermostAgileDashboard\BotMattermostStandUpSummary;

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{
    public const string SEND_TIME_INTERVAL = '00:00:59';

    /**
     * @psalm-return null|array{id:int, bot_id:int, project_id:int, send_time:string}
     */
    public function searchBotNotification(int $project_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_botmattermost_agiledashboard_notification
                WHERE project_id = ?';

        return $this->getDB()->row($sql, $project_id);
    }

    /**
     * @psalm-return list<array{notification_id:int, channel_name:string}>
     */
    public function searchChannels(int $notification_id): array
    {
        $sql = 'SELECT *
                FROM plugin_botmattermost_agiledashboard_notification_channel
                WHERE notification_id = ?';

        return $this->getDB()->run($sql, $notification_id);
    }

    public function searchAgileDashboardBotsForSummary(): ?array
    {
        $sql = 'SELECT *
                FROM plugin_botmattermost_bot
                INNER JOIN plugin_botmattermost_agiledashboard_notification
                ON plugin_botmattermost_bot.id = plugin_botmattermost_agiledashboard_notification.bot_id
                WHERE SUBTIME(CURRENT_TIME(), ?) < send_time
                  AND send_time <= CURRENT_TIME()';

        return $this->getDB()->run($sql, self::SEND_TIME_INTERVAL);
    }

    public function createNotification(array $channels, int $bot_id, int $project_id, string $send_time): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($channels, $bot_id, $project_id, $send_time): bool {
                $notification_id = $this->createBotNotification($bot_id, $project_id, $send_time);
                $this->createChannels($channels, $notification_id);

                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    public function updateNotification(array $channels, int $project_id, string $send_time): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($channels, $project_id, $send_time): bool {
                $notification_id = $this->getNotificationId($project_id);
                $this->updateBotNotification($notification_id, $send_time);
                $this->updateChannels($channels, $notification_id);

                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    public function deleteNotification(int $project_id): bool
    {
        try {
            return $this->getDB()->tryFlatTransaction(function () use ($project_id): bool {
                $this->deleteNotificationByNotificationId($this->getNotificationId($project_id));
                return true;
            });
        } catch (\PDOException $ex) {
            return false;
        }
    }

    public function deleteNotificationByBot(Bot $bot): bool
    {
        try {
            $bot_id = (int) $bot->getId();

            $sql = 'DELETE notification, channel
                FROM plugin_botmattermost_agiledashboard_notification AS notification
                INNER JOIN plugin_botmattermost_agiledashboard_notification_channel AS channel
                  ON notification.id = channel.notification_id
                WHERE bot_id = ?';

            $this->getDB()->run($sql, $bot_id);
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    private function deleteNotificationByNotificationId(int $notification_id): void
    {
        $this->deleteBotNotification($notification_id);
        $this->deleteChannels($notification_id);
    }

    private function getNotificationId(int $project_id): int
    {
        $res = $this->searchNotificationId($project_id);

        return $res['id'];
    }

    private function searchNotificationId(int $project_id): ?array
    {
        $sql = 'SELECT id
                FROM plugin_botmattermost_agiledashboard_notification
                WHERE project_id = ?';

        return $this->getDB()->row($sql, $project_id);
    }

    private function createBotNotification(int $bot_id, int $project_id, string $send_time): int
    {
        return (int) $this->getDB()->insertReturnId(
            'plugin_botmattermost_agiledashboard_notification',
            [
                'bot_id'     => $bot_id,
                'project_id' => $project_id,
                'send_time'  => $send_time,
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
            'plugin_botmattermost_agiledashboard_notification_channel',
            $data_to_insert
        );
    }

    private function updateBotNotification(int $id, string $send_time): void
    {
        $this->getDB()->update(
            'plugin_botmattermost_agiledashboard_notification',
            [
                'send_time' => $send_time,
            ],
            [
                'id' => $id,
            ]
        );
    }

    private function updateChannels(array $channels, int $notification_id): void
    {
        $this->deleteChannels($notification_id);
        if ($this->hasValue($channels)) {
            $this->createChannels($channels, $notification_id);
        }
    }

    private function deleteChannels(int $notification_id): void
    {
        $this->getDB()->delete(
            'plugin_botmattermost_agiledashboard_notification_channel',
            [
                'notification_id' => $notification_id,
            ]
        );
    }

    private function deleteBotNotification(int $id): void
    {
        $this->getDB()->delete(
            'plugin_botmattermost_agiledashboard_notification',
            [
                'id' => $id,
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
}
