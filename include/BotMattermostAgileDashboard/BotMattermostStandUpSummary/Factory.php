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

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotCreateBotNotificationException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotDeleteBotNotificationException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotUpdateBotNotificationException;

class Factory
{
    private $dao;
    private $bot_factory;
    private $logger;

    public function __construct(
        Dao $dao,
        BotFactory $bot_factory,
        BotMattermostLogger $logger
    ) {
        $this->dao         = $dao;
        $this->bot_factory = $bot_factory;
        $this->logger      = $logger;
    }

    public function getBotNotification($project_id)
    {
        $bot = false;
        if ($res = $this->dao->searchBotNotification($project_id)) {
            $channels = $this->getChannels($res['id']);

            $bot = new BotMattermostStandUpSummary(
                $this->bot_factory->getBotById($res['bot_id']),
                $channels,
                $project_id,
                $res['send_time']
            );
        }

        return $bot;
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

    public function addBotNotification(array $channels, $bot_id, $project_id, $send_time)
    {
        if (! $this->dao->createNotification($channels, $bot_id, $project_id, $send_time)) {
            throw new CannotCreateBotNotificationException();
        }
    }

    public function saveBotNotification(array $channels, $project_id, $send_time)
    {
        if (! $this->dao->updateNotification($channels, $project_id, $send_time)) {
            throw new CannotUpdateBotNotificationException();
        }
    }

    public function deleteBotNotification($project_id)
    {
        if (! $this->dao->deleteNotification($project_id)) {
            throw new CannotDeleteBotNotificationException();
        }
    }

    public function getAgileDashboardBotsForSummary()
    {
        $bots_mattermost_stand_up_summary = array();

        if ($dar  = $this->dao->searchAgileDashboardBotsForSummary()) {
            foreach ($dar as $row) {
                $project_id = $row['project_id'];

                $channels = array();
                try {
                    $channels = $this->getChannels($project_id);
                } catch (ChannelsNotFoundException $exception) {
                    $this->logger->info('No channels found');
                }
                $bots_mattermost_stand_up_summary[] = new BotMattermostStandUpSummary(
                    $this->bot_factory->getBotById($row['id']),
                    $channels,
                    $project_id,
                    $row['send_time']
                );
            }

            if (empty($bots_mattermost_stand_up_summary)) {
                $this->logger->warn('No Bots found');
            }
        }

        return $bots_mattermost_stand_up_summary;
    }
}