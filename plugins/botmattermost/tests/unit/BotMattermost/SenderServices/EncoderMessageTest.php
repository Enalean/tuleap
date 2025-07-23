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

use Tuleap\BotMattermost\Bot\Bot;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EncoderMessageTest extends TestCase
{
    private EncoderMessage $encoder_message;
    private Bot $bot;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        $this->encoder_message = new EncoderMessage();
        $this->bot             = new Bot(
            1,
            'Robot',
            'https://example.com/hook',
            'https://example.com/avatar',
            null
        );
    }

    public function testItVerifiesThatGeneratedMessageWithTextReturnsPostFormatForMattermost(): void
    {
        $message = new Message();
        $message->setText('text');

        $channel = 'channel';
        $result  = $this->encoder_message->generateJsonMessage($this->bot, $message, $channel);

        self::assertEquals(
            $result,
            '{"username":"Robot","channel":"channel","icon_url":"https:\/\/example.com\/avatar","text":"text"}'
        );
    }

    public function testItVerifiesThatGeneratedMessageWithAttachmentReturnsPostFormatForMattermost(): void
    {
        $message    = new Message();
        $attachment = new Attachment('pre-text', 'title', 'https://www.example.com', 'description');
        $channel    = 'channel';

        $message->addAttachment($attachment);

        $result = $this->encoder_message->generateJsonMessage($this->bot, $message, $channel);
        self::assertEquals(
            $result,
            '{"username":"Robot","channel":"channel","icon_url":"https:\/\/example.com\/avatar","attachments":[{"color":"#36a64f","pretext":"pre-text","title":"title","title_link":"https:\/\/www.example.com","text":"description"}]}'
        );
    }
}
