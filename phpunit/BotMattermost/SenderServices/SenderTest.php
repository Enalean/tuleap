<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class SenderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Sender
     */
    private $sender;

    private $encoder_message;
    private $botMattermost_client;
    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->encoder_message      = \Mockery::spy(\Tuleap\BotMattermost\SenderServices\EncoderMessage::class);
        $this->botMattermost_client = \Mockery::spy(\Tuleap\BotMattermost\SenderServices\ClientBotMattermost::class);
        $this->logger               = \Mockery::spy(\Tuleap\BotMattermost\BotMattermostLogger::class);

        $this->sender = new Sender(
            $this->encoder_message,
            $this->botMattermost_client,
            $this->logger
        );
    }

    public function testItVerifiedThatPushNotificationForEachChannels()
    {
        $message  = new Message();
        $channels = array('channel1', 'channel2');
        $message->setText('{"username":"toto","channel":"channel","icon_url":"https:\/\/avatar_url.com","text":"text"}');

        $bot = \Mockery::spy(\Tuleap\BotMattermost\Bot\Bot::class);
        $bot->allows()->getWebhookUrl()->andReturns('https:\/\/webhook_url.com');

        $this_botMattermost_client_sendMessage = $this->botMattermost_client->shouldReceive('sendMessage');
        $this_botMattermost_client_sendMessage->times(2);

        $this->sender->pushNotification($bot, $message, $channels);
    }
}
