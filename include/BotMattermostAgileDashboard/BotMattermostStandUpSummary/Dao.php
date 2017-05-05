<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use DataAccessObject;

class Dao extends DataAccessObject
{

    const SYSTEM_EVENT_INTERVAL = '00:30:00';

    public function searchBotNotification($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM plugin_botmattermost_agiledashboard_notification
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchChannels($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "SELECT * FROM plugin_botmattermost_agiledashboard_notification_channel
                WHERE notification_id = $notification_id";

        return $this->retrieve($sql);
    }

    public function searchAgileDashboardBotsForSummary()
    {
        $interval   = $this->da->quoteSmart(self::SYSTEM_EVENT_INTERVAL);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                INNER JOIN plugin_botmattermost_agiledashboard_notification
                ON plugin_botmattermost_bot.id = plugin_botmattermost_agiledashboard_notification.bot_id
                WHERE SUBTIME(CURRENT_TIME(), $interval) < send_time
                  AND send_time <= CURRENT_TIME()";

        return $this->retrieve($sql);
    }

    public function createNotification(array $channels, $bot_id, $project_id, $send_time)
    {
        $this->da->startTransaction();

        if (($notification_id = $this->createBotNotification($bot_id, $project_id, $send_time)) === false) {
            $this->da->rollback();

            return false;
        } else {
            if ($this->createChannels($channels, $notification_id) === false) {
                $this->da->rollback();

                return false;
            }
        }

        return $this->da->commit();
    }

    public function updateNotification(array $channels, $project_id, $send_time)
    {
        $this->da->startTransaction();

        $notification_id = $this->getNotificationId($project_id);

        if ($this->updateBotNotification($notification_id, $send_time) === false){
            $this->da->rollback();

            return false;
        } else {

            if ($this->updateChannels($channels, $notification_id) === false) {
                $this->da->rollback();

                return false;
            }
        }

        return $this->da->commit();
    }

    public function deleteNotification($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $this->da->startTransaction();

        $notification_id = $this->getNotificationId($project_id);

        if ($this->deleteBotNotification($notification_id) === false) {
            $this->da->rollback();

            return false;
        } else {
            if ($this->deleteChannels($notification_id) === false) {
                $this->da->rollback();

                return false;
            }
        }

        return $this->da->commit();
    }

    public function searchTime($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT send_time
                FROM plugin_botmattermost_agiledashboard_notification
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    private function getNotificationId($project_id)
    {
        $res = $this->searchNotificationId($project_id);

        return $res['id'];
    }

    private function searchNotificationId($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT id
                FROM plugin_botmattermost_agiledashboard_notification
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    private function createBotNotification($bot_id, $project_id, $send_time)
    {
        $bot_id     = $this->da->escapeInt($bot_id);
        $project_id = $this->da->escapeInt($project_id);
        $send_time  = $this->da->quoteSmart($send_time);

        $sql = "INSERT INTO plugin_botmattermost_agiledashboard_notification (bot_id, project_id, send_time)
                VALUES ($bot_id, $project_id, $send_time)";

        return $this->updateAndGetLastId($sql);
    }

    private function createChannels(array $channels, $notification_id)
    {
        $channels_value_sql = array();
        foreach($channels as $channel_name) {
            $channels_value_sql[] = $this->getChannelValueSqlForInsert($notification_id, $channel_name);
        }

        $sql = "INSERT INTO plugin_botmattermost_agiledashboard_notification_channel (notification_id, channel_name)
                VALUES ".implode(',', $channels_value_sql);

        return $this->update($sql);
    }

    private function getChannelValueSqlForInsert($notification_id, $channel_name)
    {
        $notification_id = $this->da->escapeInt($notification_id);
        $channel_name    = $this->da->quoteSmart($channel_name);

        return "($notification_id, $channel_name)";
    }

    private function updateBotNotification($id, $send_time)
    {
        $id        = $this->da->escapeInt($id);
        $send_time = $this->da->quoteSmart($send_time);

        $sql = "UPDATE plugin_botmattermost_agiledashboard_notification
                SET send_time = $send_time
                WHERE id = $id";

        $this->update($sql);
    }

    private function updateChannels(array $channels, $notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        if (! $this->deleteChannels($notification_id)) {
            return false;
        }
        if ($this->hasValue($channels)) {
            if(! $this->createChannels($channels, $notification_id)) {
                return false;
            }
        }

        return true;
    }

    private function deleteChannels($notification_id)
    {
        $sql = "DELETE FROM plugin_botmattermost_agiledashboard_notification_channel
                WHERE notification_id = $notification_id";

        return $this->update($sql);
    }

    private function deleteBotNotification($id)
    {
        $sql = "DELETE FROM plugin_botmattermost_agiledashboard_notification
                WHERE id = $id";

        $this->updateAndGetLastId($sql);
    }

    private function hasValue(array $array)
    {
        if (empty($array)) {
            return false;
        }

        return (trim(implode('', $array)) !== '');
    }
}
