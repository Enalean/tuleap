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

class Sender
{

    private $encoder_message;
    private $client;

    public function __construct(
        EncoderMessage $encoder_message,
        ClientBotMattermost $client
    ) {
        $this->encoder_message    = $encoder_message;
        $this->client             = $client;
    }

    /**
     * @param Bot[] $bots
     */
    public function pushNotifications(array $bots, $text)
    {
        foreach ($bots as $bot) {
            $this->pushNotificationsForEachChannels($bot, $text);
        }
    }

    public function send($post_string, $url)
    {
        try {
            $this->client->sendMessage($post_string, $url);
        } catch (Exception $ex) {
            //Do nothing
        }
    }

    private function pushNotificationsForEachChannels(Bot $bot, $text)
    {
        $channels_names = $bot->getChannelsNames();
        if (count($channels_names) > 0) {
            foreach ($channels_names as $channel) {
                $message = $this->encoder_message->generateMessage(
                    $bot,
                    $text,
                    $channel
                );
                $this->send($message, $bot->getWebhookUrl());
            }
        } else {
            $message = $this->encoder_message->generateMessage($bot, $text);
            $this->send($message, $bot->getWebhookUrl());
        }
    }
}
