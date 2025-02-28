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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\MailGateway;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_IncomingMessageInsecureBuilder;
use Tracker_Artifact_MailGateway_ArtifactDoesNotExistException;
use Tracker_Artifact_MailGateway_InvalidMailHeadersException;
use Tracker_Artifact_MailGateway_MultipleUsersExistException;
use Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException;
use Tracker_Artifact_MailGateway_TrackerDoesNotExistException;
use Tracker_Artifact_MailGateway_TrackerIdMissingException;
use Tracker_ArtifactFactory;
use TrackerFactory;
use trackerPlugin;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserManager;

final class IncomingMessageInsecureBuilderTest extends TestCase
{
    private const USER_MAIL     = 'user@example.com';
    private const TRACKER_ID    = 1;
    private const TRACKER_MAIL  = 'forge__tracker+1@example.com';
    private const ARTIFACT_ID   = 1;
    private const ARTIFACT_MAIL = 'forge__artifact+1@example.com';

    private UserManager&MockObject $user_manager;
    private TrackerFactory&MockObject $tracker_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;

    protected function setUp(): void
    {
        $this->user_manager     = $this->createMock(UserManager::class);
        $this->tracker_factory  = $this->createMock(TrackerFactory::class);
        $this->artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);
    }

    public function testItDoesNotAcceptInvalidFromHeader(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_InvalidMailHeadersException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItDoesNotAcceptInvalidToHeader(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '@example.com']);
        $incoming_mail->method('getCC')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_TrackerIdMissingException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItFindsUserAndTrackerToHeader(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([self::TRACKER_MAIL]);
        $incoming_mail->method('getCC')->willReturn([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        self::assertNotNull($user);
        self::assertNotNull($tracker);
    }

    public function testItFindsUserAndTrackerCcHeader(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([]);
        $incoming_mail->method('getCC')->willReturn([self::TRACKER_MAIL]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        self::assertNotNull($user);
        self::assertNotNull($tracker);
    }

    public function testItFindsUserAndTrackerMultipleUsers(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);
        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([self::TRACKER_MAIL, 'unknown@example.com']);
        $incoming_mail->method('getCC')->willReturn([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $user             = $incoming_message->getUser();
        $tracker          = $incoming_message->getTracker();
        self::assertNotNull($user);
        self::assertNotNull($tracker);
    }

    public function testItFindsArtifactToHeader(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(self::ARTIFACT_ID)
            ->willReturn(ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker(TrackerTestBuilder::aTracker()->build())->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([self::ARTIFACT_MAIL]);
        $incoming_mail->method('getCC')->willReturn([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $artifact         = $incoming_message->getArtifact();
        self::assertNotNull($artifact);
    }

    public function testItFindsArtifactCcHeader(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(self::ARTIFACT_ID)
            ->willReturn(ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker(TrackerTestBuilder::aTracker()->build())->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([]);
        $incoming_mail->method('getCC')->willReturn([self::ARTIFACT_MAIL]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $artifact         = $incoming_message->getArtifact();
        self::assertNotNull($artifact);
    }

    public function testItFindsArtifactMultipleUsers(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(self::ARTIFACT_ID)
            ->willReturn(ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker(TrackerTestBuilder::aTracker()->build())->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([self::ARTIFACT_MAIL, 'unknown@example.com']);
        $incoming_mail->method('getCC')->willReturn([]);

        $incoming_message = $incoming_message_builder->build($incoming_mail);
        $artifact         = $incoming_message->getArtifact();
        self::assertNotNull($artifact);
    }

    public function testItRejectsUnknownMail(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->willReturn([]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn(['unknown@example.com']);
        $incoming_mail->method('getTo')->willReturn([self::TRACKER_MAIL]);
        $incoming_mail->method('getCC')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_RecipientUserDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsMailWithMultipleUsers(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(self::TRACKER_ID)->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults(), UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([self::TRACKER_MAIL]);
        $incoming_mail->method('getCC')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_MultipleUsersExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsUnknownTracker(): void
    {
        $this->tracker_factory->method('getTrackerById')->with(99999999)->willReturn(null);
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '+99999999@example.com']);
        $incoming_mail->method('getCC')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_TrackerDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }

    public function testItRejectsUnknownArtifact(): void
    {
        $this->artifact_factory->method('getArtifactById')->with(99999999)->willReturn(null);
        $this->user_manager->method('getAllUsersByEmail')->with(self::USER_MAIL)->willReturn([UserTestBuilder::buildWithDefaults()]);

        $incoming_message_builder = new Tracker_Artifact_IncomingMessageInsecureBuilder(
            $this->user_manager,
            $this->tracker_factory,
            $this->artifact_factory
        );

        $incoming_mail = $this->createMock(IncomingMail::class);
        $incoming_mail->method('getSubject')->willReturn('Subject');
        $incoming_mail->method('getBodyText')->willReturn('Body');
        $incoming_mail->method('getFrom')->willReturn([self::USER_MAIL]);
        $incoming_mail->method('getTo')->willReturn([trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_UPDATE . '+99999999@example.com']);
        $incoming_mail->method('getCC')->willReturn([]);

        $this->expectException(Tracker_Artifact_MailGateway_ArtifactDoesNotExistException::class);
        $incoming_message_builder->build($incoming_mail);
    }
}
