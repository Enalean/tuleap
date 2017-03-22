<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

    public function addBot($bot_name, $bot_webhook_url, $bot_avatar_url)
    {
        $name           = $this->da->quoteSmart($bot_name);
        $webhook_url    = $this->da->quoteSmart($bot_webhook_url);
        $avatar_url     = $this->da->quoteSmart($bot_avatar_url);

        $sql = "INSERT INTO plugin_botmattermost_bot (name, webhook_url, avatar_url)
                VALUES ($name, $webhook_url, $avatar_url)";

        return $this->updateAndGetLastId($sql);
    }

    public function deleteBot($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "DELETE FROM plugin_botmattermost_bot
                WHERE id = $id";

        return $this->update($sql);
    }

    public function updateBot($bot_name, $bot_webhook_url, $bot_avatar_url, $id)
    {
        $name        = $this->da->quoteSmart($bot_name);
        $webhook_url = $this->da->quoteSmart($bot_webhook_url);
        $avatar_url  = $this->da->quoteSmart($bot_avatar_url);
        $id          = $this->da->escapeInt($id);

        $sql = "UPDATE plugin_botmattermost_bot
                SET name = $name,
                    webhook_url = $webhook_url,
                    avatar_url = $avatar_url
                WHERE id = $id";

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

    public function searchBotById($bot_id)
    {
        $id = $this->da->escapeInt($bot_id);

        $sql = "SELECT *
                FROM plugin_botmattermost_bot
                WHERE id = $id";

        return $this->retrieveFirstRow($sql);
    }
}
