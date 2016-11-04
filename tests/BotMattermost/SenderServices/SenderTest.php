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

require_once dirname(__FILE__).'/../../bootstrap.php';

use TuleapTestCase;

class SenderTest extends TuleapTestCase
{

    private $encoder_message;
    private $botMattermost_client;

    public function setUp()
    {
        parent::setUp();
        $this->encoder_message      = mock('Tuleap\\BotMattermost\\SenderServices\\EncoderMessage');
        $this->botMattermost_client = mock('Tuleap\\BotMattermost\\SenderServices\\ClientBotMattermost');

        $this->sender = new Sender(
            $this->encoder_message,
            $this->botMattermost_client
        );
    }

    public function itVerifiedThatPushNotification()
    {
        $text = '{"username":"toto","channel":"channel","icon_url":"https:\/\/avatar_url.com","text":"text"}';
        $bot1 = mock('Tuleap\\BotMattermost\\Bot\\Bot');
        $bot2 = mock('Tuleap\\BotMattermost\\Bot\\Bot');
        stub($bot1)->getChannelsNames()->returns(array('channel1', 'channel2'));
        stub($bot1)->getWebhookUrl()->returns('https:\/\/webhook_url.com');
        stub($bot2)->getChannelsNames()->returns(array('channel1'));
        stub($bot2)->getWebhookUrl()->returns('https:\/\/webhook_url.com');
        $bots = array($bot1, $bot2);

        $this->sender->pushNotifications($bots, $text);
        $this->botMattermost_client->expectCallCount('sendMessage', 3);
    }
}