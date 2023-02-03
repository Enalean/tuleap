<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Psr\Log\LoggerInterface;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\ChannelsNotFoundException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotDeleteBotNotificationException;
use Tuleap\BotMattermostAgileDashboard\Exception\CannotUpdateBotNotificationException;

class Factory
{
    private $dao;
    private $bot_factory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Dao $dao,
        BotFactory $bot_factory,
        LoggerInterface $logger,
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
        $channels = [];
        foreach ($dar as $row) {
            $channels[] = $row['channel_name'];
        }

        return $channels;
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

    public function deleteBotNotificationByBot(Bot $bot)
    {
        if (! $this->dao->deleteNotificationByBot($bot)) {
            throw new CannotDeleteBotNotificationException();
        }
    }

    public function getAgileDashboardBotsForSummary()
    {
        $bots_mattermost_stand_up_summary = [];

        if ($dar  = $this->dao->searchAgileDashboardBotsForSummary()) {
            foreach ($dar as $row) {
                $project_id = $row['project_id'];

                $channels = [];
                try {
                    $channels = $this->getChannels($project_id);
                } catch (ChannelsNotFoundException $exception) {
                    $this->logger->info('No channels found');
                }
                $bots_mattermost_stand_up_summary[] = new BotMattermostStandUpSummary(
                    $this->bot_factory->getBotById($row['bot_id']),
                    $channels,
                    $project_id,
                    $row['send_time']
                );
            }

            if (empty($bots_mattermost_stand_up_summary)) {
                $this->logger->debug('No Bots found');
            }
        }

        return $bots_mattermost_stand_up_summary;
    }
}
