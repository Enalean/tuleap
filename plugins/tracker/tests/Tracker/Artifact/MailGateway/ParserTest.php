<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_MailGateway_Parser_BaseTest extends TuleapTestCase {

    protected $plain_plus_html_reply;
    protected $parser;
    protected $recipient_factory;

    public function setUp() {
        parent::setUp();
        $fixtures_dir = dirname(__FILE__) .'/_fixtures';

        $this->plain_reply            = file_get_contents($fixtures_dir .'/reply-comment.plain.eml');
        $this->html_reply             = file_get_contents($fixtures_dir .'/reply-comment.html.eml');
        $this->plain_plus_html_reply  = file_get_contents($fixtures_dir .'/reply-comment.plain+html.eml');
        $this->html_plus_plain_reply  = file_get_contents($fixtures_dir .'/reply-comment.html+plain.eml');
        $this->with_attachment_reply  = file_get_contents($fixtures_dir .'/reply-comment.(plain+html)+attachment.eml');
        $this->expected_followup_text = file_get_contents($fixtures_dir .'/expected_followup.text.txt');

        $this->recipient_factory = mock('Tracker_Artifact_MailGatewayRecipientFactory');

        $this->parser = new Tracker_Artifact_MailGateway_Parser($this->recipient_factory);
    }
}

class Tracker_Artifact_MailGateway_Parser_BodyTest extends Tracker_Artifact_MailGateway_Parser_BaseTest {

    public function setUp() {
        parent::setUp();
        stub($this->recipient_factory)
            ->getFromEmail()
            ->returns(mock('Tracker_Artifact_MailGatewayRecipient'));
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormat() {
        $incoming_message = $this->parser->parse($this->plain_plus_html_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatEvenIfTheHtmlPartIsTheFirstOne() {
        $incoming_message = $this->parser->parse($this->html_plus_plain_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatEvenIfThereIsAnAttachment() {
        $incoming_message = $this->parser->parse($this->with_attachment_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatWhenThereIsOnlyATextPlain() {
        $incoming_message = $this->parser->parse($this->plain_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsEmptyStringWhenNoTextPlain() {
        $incoming_message = $this->parser->parse($this->html_reply);

        $this->assertIdentical($incoming_message->getBody(), '');
    }
}

class Tracker_Artifact_MailGateway_Parser_RecipientTest extends Tracker_Artifact_MailGateway_Parser_BaseTest {

    public function setUp() {
        parent::setUp();
    }

    public function itReturnsTheCorrespondingRecipient() {
        $recipient = mock('Tracker_Artifact_MailGatewayRecipient');
        stub($this->recipient_factory)
            ->getFromEmail('<1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>')
            ->returns($recipient);

        $incoming_message = $this->parser->parse($this->plain_plus_html_reply);

        $this->assertIdentical($incoming_message->getRecipient(), $recipient);
    }
}