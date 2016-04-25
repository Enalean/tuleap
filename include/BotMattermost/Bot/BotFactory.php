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

use Tuleap\BotMattermost\Exception\CannotAccessDataBaseException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;

class BotFactory
{
    private $dao;

    public function __construct(BotDao $bot_dao)
    {
        $this->dao = $bot_dao;
    }

    /**
     * @return Bot
     */
    public function save($bot_name, $bot_webhook_url)
    {
        $id = $this->dao->addBot($bot_name, $bot_webhook_url);
        if (!$id) {
            throw new CannotCreateBotException($GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_error'));
        }
        return new Bot($id, $bot_name, $bot_webhook_url);
    }

    /**
     * @return Bot[]
     */
    public function getBots()
    {
        $dar = $this->dao->searchBots();
        if ($dar === false) {
            throw new CannotAccessDataBaseException($GLOBALS['Language']->getText('plugin_botmattermost', 'configuration_alert_error'));
        }
        $bots = array();
        foreach ($dar as $row) {
            $bot = new Bot($row['id'], $row['name'], $row['webhook_url']);
            $bots[] = $bot;
        }
        return $bots;
    }
}
