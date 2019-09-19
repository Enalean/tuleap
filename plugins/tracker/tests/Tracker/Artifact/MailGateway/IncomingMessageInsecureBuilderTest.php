<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

require_once __DIR__.'/../../../bootstrap.php';

class Tracker_Artifact_IncomingMessageInsecureBuilderTest extends TuleapTestCase
{
    public const USER_MAIL     = 'user@example.com';
    public const TRACKER_ID    = 1;
    public const TRACKER_MAIL  = 'forge__tracker+1@example.com';
    public const ARTIFACT_ID   = 1;
    public const ARTIFACT_MAIL = 'forge__artifact+1@example.com';

    private $user_manager;
    private $tracker_factory;
    private $artifact_factory;

    public function setUp()
    {
        $this->user_manager     = mock('UserManager');
        $this->tracker_factory  = mock('TrackerFactory');
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
    }

    public function itDoesNotAcceptInvalidFromHeader()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([]);

        $this->expectException('Tracker_Artifact_MailGateway_InvalidMailHeadersException');
        $incoming_message_builder->build($incoming_mail);
    }

    public function itDoesNotAcceptInvalidToHeader()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '@example.com']);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        try {
            $incoming_message_builder->build($incoming_mail);
            $this->fail();
        } catch (Tracker_Artifact_MailGateway_TrackerIdMissingException $e) {
        }
    }

    public function itFindsUserAndTrackerToHeader()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::TRACKER_MAIL]);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsUserAndTrackerCcHeader()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([]);
        $incoming_mail->shouldReceive('getCC')->andReturns([self::TRACKER_MAIL]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsUserAndTrackerMultipleUsers()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));
        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::TRACKER_MAIL, 'unknown@example.com']);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        $this->assertNotNull($user);
        $this->assertNotNull($tracker);
    }

    public function itFindsArtifactToHeader()
    {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::ARTIFACT_MAIL]);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $incoming_message  = $incoming_message_builder->build($incoming_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itFindsArtifactCcHeader()
    {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([]);
        $incoming_mail->shouldReceive('getCC')->andReturns([self::ARTIFACT_MAIL]);

        $incoming_message  = $incoming_message_builder->build($incoming_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itFindsArtifactMultipleUsers()
    {
        $artifact_mock = stub('Tracker_Artifact')->getTracker()->returns(mock('Tracker'));
        stub($this->artifact_factory)->getArtifactById(self::ARTIFACT_ID)->returns($artifact_mock);
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::ARTIFACT_MAIL, 'unknown@example.com']);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $incoming_message  = $incoming_message_builder->build($incoming_mail);
        $artifact          = $incoming_message->getArtifact();
        $this->assertNotNull($artifact);
    }

    public function itRejectsUnknownMail()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail()->returns([]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns(['unknown@example.com']);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::TRACKER_MAIL]);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $this->expectException('Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException');
        $incoming_message_builder->build($incoming_mail);
    }

    public function itRejectsMailWithMultipleUsers()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser'), mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([self::TRACKER_MAIL]);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $this->expectException('Tracker_Artifact_MailGateway_MultipleUsersExistException');
        $incoming_message_builder->build($incoming_mail);
    }

    public function itRejectsUnknownTracker()
    {
        stub($this->tracker_factory)->getTrackerById(self::TRACKER_ID)->returns(mock('Tracker'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '+99999999@example.com']);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $this->expectException('Tracker_Artifact_MailGateway_TrackerDoesNotExistException');
        $incoming_message_builder->build($incoming_mail);
    }

    public function itRejectsUnknownArtifact()
    {
        stub($this->artifact_factory)->getArtifactById(self::TRACKER_ID)->returns(mock('Tracker_Artifact'));
        stub($this->user_manager)->getAllUsersByEmail(self::USER_MAIL)->returns(array(mock('PFUser')));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([self::USER_MAIL]);
        $incoming_mail->shouldReceive('getTo')->andReturns([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE . '+99999999@example.com']);
        $incoming_mail->shouldReceive('getCC')->andReturns([]);

        $this->expectException('Tracker_Artifact_MailGateway_ArtifactDoesNotExistException');
        $incoming_message_builder->build($incoming_mail);
    }
}
