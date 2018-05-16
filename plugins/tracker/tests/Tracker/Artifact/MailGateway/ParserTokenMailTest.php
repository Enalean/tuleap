<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_Artifact_MailGateway_Parser_TokenMail_BaseTest extends TuleapTestCase {

    private $parser;
    private $incoming_message_factory;
    protected $plain_plus_html_reply;
    protected $recipient_factory;
    protected $fixtures_dir;

    public function setUp() {
        parent::setUp();
        $this->fixtures_dir = dirname(__FILE__) .'/_fixtures';

        $this->plain_reply            = file_get_contents($this->fixtures_dir .'/reply-comment.plain.eml');
        $this->html_reply             = file_get_contents($this->fixtures_dir .'/reply-comment.html.eml');
        $this->plain_plus_html_reply  = file_get_contents($this->fixtures_dir .'/reply-comment.plain+html.eml');
        $this->html_plus_plain_reply  = file_get_contents($this->fixtures_dir .'/reply-comment.html+plain.eml');
        $this->with_attachment_reply  = file_get_contents($this->fixtures_dir .'/reply-comment.(plain+html)+attachment.eml');
        $this->expected_followup_text = file_get_contents($this->fixtures_dir .'/expected_followup.text.txt');

        $this->recipient_factory           = mock('Tracker_Artifact_MailGateway_RecipientFactory');
        $incoming_message_insecure_builder = mock('Tracker_Artifact_IncomingMessageInsecureBuilder');
        $tracker_config                    = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');
        $tracker_config->setReturnValue('isTokenBasedEmailgatewayEnabled', true);

        $incoming_message_token_builder    = new Tracker_Artifact_IncomingMessageTokenBuilder($this->recipient_factory);
        $this->parser                      = new Tracker_Artifact_MailGateway_Parser();
        $this->incoming_message_factory    = new Tracker_Artifact_MailGateway_IncomingMessageFactory(
            $tracker_config,
            $incoming_message_token_builder,
            $incoming_message_insecure_builder
        );
    }

    protected function parseEmailToIncomingMessage($raw_email) {
        $raw_email_parsed = $this->parser->parse($raw_email);
        return $this->incoming_message_factory->build($raw_email_parsed);
    }
}

class Tracker_Artifact_MailGateway_Parser_TokenMail_BodyTest extends Tracker_Artifact_MailGateway_Parser_TokenMail_BaseTest {

    public function setUp() {
        parent::setUp();
        $recipient = mock('Tracker_Artifact_MailGateway_Recipient');
        $artifact  = mock('Tracker_Artifact');
        $artifact->setReturnValue('getTracker', mock('Tracker'));
        $recipient->setReturnValue('getArtifact', $artifact);
        $recipient->setReturnValue('getUser', mock('PFUser'));
        stub($this->recipient_factory)
            ->getFromEmail()
            ->returns($recipient);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormat() {
        $incoming_message = $this->parseEmailToIncomingMessage($this->plain_plus_html_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatEvenIfTheHtmlPartIsTheFirstOne() {
        $incoming_message = $this->parseEmailToIncomingMessage($this->html_plus_plain_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatEvenIfThereIsAnAttachment() {
        $incoming_message = $this->parseEmailToIncomingMessage($this->with_attachment_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsTheFollowUpCommentToAddInTextPlainFormatWhenThereIsOnlyATextPlain() {
        $incoming_message = $this->parseEmailToIncomingMessage($this->plain_reply);

        $this->assertIdentical($incoming_message->getBody(), $this->expected_followup_text);
    }

    public function itReturnsEmptyStringWhenNoTextPlain() {
        $incoming_message = $this->parseEmailToIncomingMessage($this->html_reply);

        $this->assertIdentical($incoming_message->getBody(), '');
    }
}

class Tracker_Artifact_MailGateway_Parser_TokenMail_RecipientTest extends Tracker_Artifact_MailGateway_Parser_TokenMail_BaseTest {

    public function setUp() {
        parent::setUp();
    }

    public function itReturnsTheCorrespondingRecipient() {
        $recipient = mock('Tracker_Artifact_MailGateway_Recipient');
        $artifact  = mock('Tracker_Artifact');
        $user      = mock('PFuser');
        $artifact->setReturnValue('getTracker', mock('Tracker'));
        $recipient->setReturnValue('getArtifact', $artifact);
        $recipient->setReturnValue('getUser', $user);
        stub($this->recipient_factory)
            ->getFromEmail('<1661-08c450c149f11d38955ad983a9b3b857-107-1234@crampons.cro.example.com>')
            ->returns($recipient);

        $incoming_message = $this->parseEmailToIncomingMessage($this->plain_plus_html_reply);

        $this->assertIdentical($incoming_message->getArtifact(), $artifact);
        $this->assertIdentical($incoming_message->getUser(), $user);
    }
}
