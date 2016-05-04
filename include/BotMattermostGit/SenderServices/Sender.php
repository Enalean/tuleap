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

namespace Tuleap\BotMattermostGit\SenderServices;

use GitRepository;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\BotMattermostGit\SenderServices\EncoderMessage;
use Tuleap\BotMattermostGit\SenderServices\NotificationMaker;
use Guzzle\Http\Client;
use PFUser;

require_once '/usr/share/php-guzzle/guzzle.phar';

class Sender
{

    private $encoder_message;
    private $notification_maker;

    public function __construct(EncoderMessage $encoder_message, NotificationMaker $notification_maker)
    {
        $this->encoder_message    = $encoder_message;
        $this->notification_maker = $notification_maker;
    }

    /**
     * @param Bot[] $bots
     */
    public function pushGitNotifications(
        array $bots,
        GitRepository $repository,
        PFUser $user,
        $newrev,
        $refname
    ) {
        $text = $this->notification_maker->makeGitNotificationText(
            $repository,
            $user,
            $newrev,
            $refname
        );
        foreach ($bots as $bot) {
            $message = $this->encoder_message->generateMessage($bot, $text);
            $this->send($message, $bot->getWebhookUrl());
        }
    }

    public function send($post_string, $url)
    {
        $client = new Client('', array(
            Client::CURL_OPTIONS => array(
                CURLOPT_SSLVERSION     => 1,
                CURLOPT_SSL_VERIFYPEER => false
                )
            )
        );

        $request = $client->post(
            $url,
            array('Content-type' => 'application/json'),
            $post_string
        );

        try {
            $response = $request->send();
        } catch (\Exception $ex) {
            $ex->getMessage();
        }
    }
}
