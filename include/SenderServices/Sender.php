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

namespace Tuleap\BotMattermost\SenderServices;

use Exception;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServicesException\Exception\HasNoMessageContentException;
use Tuleap\BotMattermostGit\SenderServices\Attachment;

class Sender
{

    const DEFAULT_CHANNEL = '';

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

    public function pushNotification(Bot $bot, Message $message, array $channels)
    {
        $this->logger->debug('bot: #'.$bot->getId().' '.$bot->getName());
        $this->logger->debug('channels: '.implode(', ', $channels));

        if (! empty($channels)) {
            foreach ($channels as $channel) {
                $this->pushNotificationByChannel($bot, $message, $channel);
            }
        } else {
            $this->pushNotificationWithoutChannel($bot, $message);
        }
    }

    private function pushNotificationByChannel(Bot $bot, Message $message, $channel) {
        $this->logger->debug('channel: '.$channel);
        $this->generateAndSendNotification($bot, $message, $channel);
    }

    private function pushNotificationWithoutChannel(Bot $bot, Message $message) {
        $this->logger->debug('No channel specified, default channel will be used');
        $this->generateAndSendNotification($bot, $message, self::DEFAULT_CHANNEL);
    }

    private function generateAndSendNotification(Bot $bot, Message $message, $channel)
    {
        try {
            $json_message = $this->encoder_message->generateJsonMessage($bot, $message, $channel);
            $this->send($json_message, $bot->getWebhookUrl());
        } catch (HasNoMessageContentException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function send($post_string, $url)
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
}
