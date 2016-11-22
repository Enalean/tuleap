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

namespace Tuleap\BotMattermost\SenderServices;

use Exception;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\BotMattermostLogger;

class Sender
{

    private $encoder_message;
    private $client;
    private $logger;

    public function __construct(
        EncoderMessage $encoder_message,
        ClientBotMattermost $client,
        BotMattermostLogger $logger
    ) {
        $this->encoder_message = $encoder_message;
        $this->client          = $client;
        $this->logger          = $logger;
    }

    /**
     * @param Bot[] $bots
     */
    public function pushNotifications(array $bots, $text)
    {
        $this->logger->debug('text: '.$text);
        if (empty($bots)) {
            $this->logger->warn('no bots found');
        }
        foreach ($bots as $bot) {
            $this->logger->debug('bot: #'.$bot->getId().' '.$bot->getName());
            $this->pushNotificationsForEachChannels($bot, $text);
        }

    }

    public function send($post_string, $url)
    {
        $this->logger->debug('post string: '.$post_string);
        $this->logger->debug('url: '.$url);
        try {
            $this->client->sendMessage($post_string, $url);
            $this->logger->info('message send');
        } catch (Exception $ex) {
            $this->logger->error('send Failed', $ex);
        }
    }

    private function pushNotificationsForEachChannels(Bot $bot, $text)
    {
        $channels_names = $bot->getChannelsNames();
        if (count($channels_names) > 0) {
            foreach ($channels_names as $channel) {
                $this->logger->debug('channel: '.$channel);
                $message = $this->encoder_message->generateMessage(
                    $bot,
                    $text,
                    $channel
                );
                $this->send($message, $bot->getWebhookUrl());
            }
        } else {
            $this->logger->debug('No channel specified');
            $message = $this->encoder_message->generateMessage($bot, $text);
            $this->send($message, $bot->getWebhookUrl());
        }
    }
}
