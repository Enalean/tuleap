<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DataAccessObject;

class BotDao extends DataAccessObject
{

    public function searchBots()
    {
        $sql = "SELECT * FROM plugin_botmattermost_bot";

        return $this->retrieve($sql);
    }

    public function addBotAndChannels(
        $bot_name,
        $bot_webhook_url,
        $bot_avatar_url,
        array $bot_channels_names
    ) {
        $this->da->startTransaction();

        $id = $this->addBot($bot_name, $bot_webhook_url, $bot_avatar_url);
        if(! $id) {
            $this->da->rollback();
            return false;
        } else {
            $dar = $this->addChannels($id, $bot_channels_names);
            if($dar === false) {
                $this->da->rollback();
                return false;
            }
        }

        return $this->da->commit();
    }

    private function addBot($bot_name, $bot_webhook_url, $bot_avatar_url)
    {
        $name           = $this->da->quoteSmart($bot_name);
        $webhook_url    = $this->da->quoteSmart($bot_webhook_url);
        $avatar_url     = $this->da->quoteSmart($bot_avatar_url);

        $sql = "INSERT INTO plugin_botmattermost_bot (name, webhook_url, avatar_url)
                VALUES ($name, $webhook_url, $avatar_url)";

        return $this->updateAndGetLastId($sql);
    }

    private function addChannels($bot_id, $bot_channels_names)
    {
        $channels = array();
        foreach($bot_channels_names as $bot_channel_name) {
            $channels[] = $this->getChannelValueSql($bot_id, $bot_channel_name);
        }

        $sql = "INSERT INTO plugin_botmattermost_channel (bot_id, name)
                VALUES ".implode(',', $channels);

        return $this->update($sql);
    }

    private function getChannelValueSql($bot_id, $bot_channel_name)
    {
        $id           = $this->da->escapeInt($bot_id);
        $channel_name = $this->da->quoteSmart($bot_channel_name);

        return "($id, $channel_name)";
    }

    public function deleteBotAndChannelsByBotId($bot_id)
    {
        $this->da->startTransaction();

        $dar = $this->deleteBotById($bot_id);
        if ($dar === false) {
            $this->da->rollback();
            return false;
        } else {
            $dar = $this->deleteChannelsByBotId($bot_id);
            if ($dar === false) {
                $this->da->rollback();
                return false;
            }
        }

        return $this->da->commit();
    }

    private function deleteBotById($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "DELETE FROM plugin_botmattermost_bot
                WHERE id = $id";

        return $this->update($sql);
    }

    private function deleteChannelsByBotId($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "DELETE FROM plugin_botmattermost_channel
                WHERE bot_id = $id";

        return $this->update($sql);
    }

    public function searchBotByNameAndByWebhookUrl($bot_name, $bot_webhook_url)
    {
        $name        = $this->da->quoteSmart($bot_name);
        $webhook_url = $this->da->quoteSmart($bot_webhook_url);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE name = $name
                    AND webhook_url = $webhook_url";

        return $this->retrieveFirstRow($sql);
    }

    public function searchChannelsByBotId($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "SELECT *
                FROM plugin_botmattermost_channel
                WHERE bot_id = $id";

        return $this->retrieve($sql);
    }

    public function searchBotById($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE id = $id";

        return $this->retrieveFirstRow($sql);
    }
}
