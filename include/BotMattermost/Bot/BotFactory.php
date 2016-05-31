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

use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;

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
    public function save(
        $bot_name,
        $bot_webhook_url,
        $bot_avatar_url,
        $bot_channels_names
    ) {
        $channels_names = array_map(
            'trim',
            explode(PHP_EOL, $bot_channels_names)
        );
        if (! $this->doesBotAlreadyExist($bot_name, $bot_webhook_url)) {
            $id = $this->dao->addBotAndChannels(
                $bot_name,
                $bot_webhook_url,
                $bot_avatar_url,
                $channels_names
            );
            if (! $id) {
                throw new CannotCreateBotException();
            }
        } else {
            throw new BotAlreadyExistException();
        }

        return new Bot(
            $id,
            $bot_name,
            $bot_webhook_url,
            $bot_avatar_url,
            $channels_names
        );
    }

    public function deleteBotById($id)
    {
        $dar = $this->dao->deleteBotAndChannelsByBotId($id);
        if ($dar === false) {
            throw new CannotDeleteBotException();
        }
    }

    /**
     * @return Bot[]
     */
    public function getBots()
    {
        $dar = $this->dao->searchBots();
        if ($dar === false) {
            throw new BotNotFoundException();
        }
        $bots = array();
        foreach ($dar as $row) {
            $id = $row['id'];
            try {
                $channels = $this->getChannelsByBotId($id);
            } catch (ChannelsNotFoundException $e) {
                throw $e;
            }

            $bots[] = new Bot(
                $id,
                $row['name'],
                $row['webhook_url'],
                $row['avatar_url'],
                $channels
            );
        }

        return $bots;
    }

    public function doesBotAlreadyExist($name, $webhook_url)
    {
        return $this->dao->searchBotByNameAndByWebhookUrl($name, $webhook_url);
    }

    /**
     * @return array
     */
    private function getChannelsByBotId($bot_id)
    {
        $dar = $this->dao->searchChannelsByBotId($bot_id);
        if ($dar === false) {
            throw new ChannelsNotFoundException();
        }
        $channels = array();
        foreach($dar as $row) {
            $channels[] = $row['name'];
        }

        return $channels;
    }

    public function getBotById($bot_id)
    {
        $row = $this->dao->searchBotById($bot_id);
        if ($row === null) {
            throw new BotNotFoundException();
        }
        try {
            $channels = $this->getChannelsByBotId($bot_id);
        } catch (ChannelsNotFoundException $e) {
            throw $e;
        }

        return new Bot(
            $bot_id,
            $row['name'],
            $row['webhook_url'],
            $row['avatar_url'],
            $channels
        );
    }
}
