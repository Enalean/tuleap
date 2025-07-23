<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Psr\Log\NullLogger;
use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SenderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ClientBotMattermost
     */
    private $botMattermost_client;

    private Sender $sender;
    private EncoderMessage $encoder_message;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->encoder_message      = new EncoderMessage();
        $this->botMattermost_client = $this->createMock(ClientBotMattermost::class);

        $this->sender = new Sender(
            $this->encoder_message,
            $this->botMattermost_client,
            new NullLogger()
        );
    }

    public function testItVerifiedThatPushNotificationForEachChannels(): void
    {
        $message  = new Message();
        $channels = ['channel1', 'channel2'];
        $message->setText('{"username":"toto","channel":"channel","icon_url":"https:\/\/example.com\/hook","text":"text"}');

        $bot = new Bot(
            1,
            'Robot',
            'https://example.com/hook',
            'https://example.com/avatar',
            null
        );

        $this->botMattermost_client
            ->expects($this->exactly(2))
            ->method('sendMessage');

        $this->sender->pushNotification($bot, $message, $channels);
    }
}
