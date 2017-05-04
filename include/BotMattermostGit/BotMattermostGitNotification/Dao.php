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

namespace Tuleap\BotMattermostGit\BotMattermostGitNotification;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function searchBotNotification($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT * FROM plugin_botmattermost_git_notification
                WHERE repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchChannels($notification_id)
    {
        $notification_id = $this->da->escapeInt($notification_id);

        $sql = "SELECT * FROM plugin_botmattermost_git_notification_channel
                WHERE notification_id = $notification_id";

        return $this->retrieve($sql);
    }

    public function createNotification(array $channels, $bot_id, $repository_id)
    {
        $this->da->startTransaction();

        $notification_id = $this->createBotNotification($bot_id, $repository_id);

        if ($notification_id === false) {
            $this->da->rollback();

            return false;
        }

        if ($this->createChannels($channels, $notification_id) === false) {
            $this->da->rollback();

            return false;
        }

        return $this->da->commit();
    }

    public function updateNotification(array $channels, $repository_id)
    {
        $this->da->startTransaction();

        $notification_id = $this->getNotificationId($repository_id);

        if ($notification_id === false){
            $this->da->rollback();

            return false;
        }

        if ($this->updateChannels($channels, $notification_id) === false) {
            $this->da->rollback();

            return false;
        }

        return $this->da->commit();
    }

    public function deleteNotification($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $this->da->startTransaction();

        $notification_id = $this->getNotificationId($repository_id);

        if ($this->deleteBotNotification($notification_id) === false) {
            $this->da->rollback();

            return false;
        }

        if ($this->deleteChannels($notification_id) === false) {
            $this->da->rollback();

            return false;
        }

        return $this->da->commit();
    }

    private function getNotificationId($repository_id)
    {
        $res = $this->searchNotificationId($repository_id);

        return $res['id'];
    }

    private function getChannelValueSqlForInsert($notification_id, $channel_name)
    {
        $notification_id = $this->da->escapeInt($notification_id);
        $channel_name    = $this->da->quoteSmart($channel_name);

        return "($notification_id, $channel_name)";
    }


    private function searchNotificationId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT id
                FROM plugin_botmattermost_git_notification
                WHERE repository_id = $repository_id";

        return $this->retrieveFirstRow($sql);
    }

    private function createBotNotification($repository_id, $bot_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $bot_id        = $this->da->escapeInt($bot_id);

        $sql = "INSERT INTO plugin_botmattermost_git_notification(repository_id, bot_id)
                VALUES ($repository_id, $bot_id)";

        return $this->updateAndGetLastId($sql);
    }

    private function createChannels(array $channels, $notification_id)
    {
        $channels_value_sql = array();
        foreach($channels as $channel_name) {
            $channels_value_sql[] = $this->getChannelValueSqlForInsert($notification_id, $channel_name);
        }

        $sql = "INSERT INTO plugin_botmattermost_git_notification_channel (notification_id, channel_name)
                VALUES ".implode(',', $channels_value_sql);

        return $this->update($sql);
    }

    private function updateChannels(array $channels, $notification_id)
    {

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

    private function deleteBotNotification($id)
    {
        $sql = "DELETE FROM plugin_botmattermost_git_notification
                WHERE id = $id";

        return $this->update($sql);
    }

    private function deleteChannels($notification_id)
    {
        $sql = "DELETE FROM plugin_botmattermost_git_notification_channel
                WHERE notification_id = $notification_id";

        return $this->update($sql);
    }

    private function hasValue(array $array)
    {
        if (empty($array)) {
            return false;
        }

        return (trim(implode('', $array)) !== '');
    }
}
