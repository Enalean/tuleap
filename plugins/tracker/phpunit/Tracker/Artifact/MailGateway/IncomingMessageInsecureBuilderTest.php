<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Artifact\MailGateway\IncomingMail;

require_once __DIR__ . '/../../../bootstrap.php';

class IncomingMessageInsecureBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const USER_MAIL     = 'user@example.com';
    public const TRACKER_ID    = 1;
    public const TRACKER_MAIL  = 'forge__tracker+1@example.com';
    public const ARTIFACT_ID   = 1;
    public const ARTIFACT_MAIL = 'forge__artifact+1@example.com';

    private $user_manager;
    private $tracker_factory;
    private $artifact_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user_manager     = \Mockery::spy(\UserManager::class);
        $this->tracker_factory  = \Mockery::spy(\TrackerFactory::class);
        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
    }

    public function testItDoesNotAcceptInvalidFromHeader(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = Mockery::mock(IncomingMail::class);
        $incoming_mail->shouldReceive('getSubject')->andReturns('Subject');
        $incoming_mail->shouldReceive('getBodyText')->andReturns('Body');
        $incoming_mail->shouldReceive('getFrom')->andReturns([]);

        $this->expectException(\Tracker_Artifact_MailGateway_InvalidMailHeadersException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItDoesNotAcceptInvalidToHeader(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

        $this->expectException(Tracker_Artifact_MailGateway_TrackerIdMissingException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItFindsUserAndTrackerToHeader(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

    public function testItFindsUserAndTrackerCcHeader(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

    public function testItFindsUserAndTrackerMultipleUsers(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));
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

    public function testItFindsArtifactToHeader(): void
    {
        $artifact_mock = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getTracker')->andReturns(\Mockery::spy(\Tracker::class))->getMock();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(self::ARTIFACT_ID)->andReturns($artifact_mock);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

    public function testItFindsArtifactCcHeader(): void
    {
        $artifact_mock = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getTracker')->andReturns(\Mockery::spy(\Tracker::class))->getMock();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(self::ARTIFACT_ID)->andReturns($artifact_mock);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

    public function testItFindsArtifactMultipleUsers(): void
    {
        $artifact_mock = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getTracker')->andReturns(\Mockery::spy(\Tracker::class))->getMock();
        $this->artifact_factory->shouldReceive('getArtifactById')->with(self::ARTIFACT_ID)->andReturns($artifact_mock);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

    public function testItRejectsUnknownMail(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->andReturns([]);

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

        $this->expectException(\Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsMailWithMultipleUsers(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class), \Mockery::spy(\PFUser::class)));

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

        $this->expectException(\Tracker_Artifact_MailGateway_MultipleUsersExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsUnknownTracker(): void
    {
        $this->tracker_factory->shouldReceive('getTrackerById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

        $this->expectException(\Tracker_Artifact_MailGateway_TrackerDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsUnknownArtifact(): void
    {
        $this->artifact_factory->shouldReceive('getArtifactById')->with(self::TRACKER_ID)->andReturns(\Mockery::spy(\Tracker_Artifact::class));
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with(self::USER_MAIL)->andReturns(array(\Mockery::spy(\PFUser::class)));

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

        $this->expectException(\Tracker_Artifact_MailGateway_ArtifactDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }
}
