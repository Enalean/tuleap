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

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermostGit\Exception\CannotCreateBotNotificationException;
use Tuleap\BotMattermostGit\Exception\CannotDeleteBotNotificationException;
use Tuleap\BotMattermostGit\Exception\CannotUpdateBotNotificationException;

class Factory
{

    private $dao;
    private $bot_factory;

    public function __construct(Dao $bot_git_dao, BotFactory $bot_factory)
    {
        $this->dao         = $bot_git_dao;
        $this->bot_factory = $bot_factory;
    }

    public function getBotNotification($repository_id)
    {
        $bot = false;
        if ($res = $this->dao->searchBotNotification($repository_id)) {
            $channels = $this->getChannels($res['id']);

            $bot = new BotMattermostGitNotification(
                $this->bot_factory->getBotById($res['bot_id']),
                $repository_id,
                $channels
            );
        }

        return $bot;
    }

    public function addBotNotification(array $channels, $bot_id, $repository_id)
    {
        if (! $this->dao->createNotification($channels, $bot_id, $repository_id)) {
            throw new CannotCreateBotNotificationException();
        }
    }

    public function saveBotNotification(array $channels, $repository_id)
    {
        if (! $this->dao->updateNotification($channels, $repository_id)) {
            throw new CannotUpdateBotNotificationException();
        }
    }

    public function deleteBotNotification($repository_id)
    {
        if (! $this->dao->deleteNotification($repository_id)) {
            throw new CannotDeleteBotNotificationException();
        }
    }

    /**
     * @return array
     */
    private function getChannels($notification_id)
    {
        $dar = $this->dao->searchChannels($notification_id);
        if ($dar === false) {
            throw new ChannelsNotFoundException();
        }
        $channels = array();
        foreach($dar as $row) {
            $channels[] = $row['channel_name'];
        }

        return $channels;
    }
}
