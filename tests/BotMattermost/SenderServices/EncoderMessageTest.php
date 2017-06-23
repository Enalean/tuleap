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

require_once dirname(__FILE__) . '/../../bootstrap.php';

use Tuleap\BotMattermostGit\SenderServices\Attachment;
use TuleapTestCase;

class EncoderMessageTest extends TuleapTestCase
{

    private $encoder_message;
    private $bot;

    public function setUp()
    {
        parent::setUp();
        $this->bot             = mock('Tuleap\\BotMattermost\\Bot\\Bot');
        $this->encoder_message = new EncoderMessage();
    }

    public function itVerifiesThatGeneratedMessageWithTextReturnsPostFormatForMattermost()
    {
        $message = new Message();
        $channel = "channel";
        stub($this->bot)->getName()->returns("toto");
        stub($this->bot)->getAvatarUrl()->returns("https://avatar_url.com");
        $message->setText("text");

        $result = $this->encoder_message->generateJsonMessage($this->bot, $message, $channel);
        $this->assertEqual(
            $result,
            '{"username":"toto","channel":"channel","icon_url":"https:\/\/avatar_url.com","text":"text"}'
        );
    }

    public function itVerifiesThatGeneratedMessageWithAttachmentReturnsPostFormatForMattermost()
    {
        $message    = new Message();
        $attachment = new Attachment('pre-text', 'title', 'https://www.example.com', 'description');
        $channel    = "channel";
        stub($this->bot)->getName()->returns("toto");
        stub($this->bot)->getAvatarUrl()->returns("https://avatar_url.com");
        $message->addAttachment($attachment);

        $result = $this->encoder_message->generateJsonMessage($this->bot, $message, $channel);
        $this->assertEqual(
            $result,
            '{"username":"toto","channel":"channel","icon_url":"https:\/\/avatar_url.com","attachments":[{"color":"#36a64f","pretext":"pre-text","title":"title","title_link":"https:\/\/www.example.com","text":"description"}]}'
        );
    }
}
