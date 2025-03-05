<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tracker;
use Tracker_Artifact_Changeset_IncomingMailDao;
use Tracker_Artifact_MailGateway_CitationStripper;
use Tracker_Artifact_MailGateway_IncomingMessage;
use Tracker_Artifact_MailGateway_IncomingMessageFactory;
use Tracker_Artifact_MailGateway_Notifier;
use Tracker_Artifact_MailGateway_TokenMailGateway;
use Tracker_ArtifactByEmailStatus;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MailGatewayTokenTest extends TestCase
{
    private const BODY          = 'justaucorps';
    private const STRIPPED_BODY = 'stripped justaucorps';

    private PFUser $user;
    private Tracker_Artifact_MailGateway_TokenMailGateway $mailgateway;
    private Artifact&MockObject $artifact;
    private Tracker_Artifact_Changeset_IncomingMailDao&MockObject $incoming_mail_dao;
    private MailGatewayConfig&MockObject $tracker_config;
    private Tracker&MockObject $tracker;
    private Tracker_Artifact_MailGateway_IncomingMessage&MockObject $incoming_message;
    private IncomingMail&MockObject $incoming_mail;
    private TrackerArtifactCreator&MockObject $artifact_creator;

    protected function setUp(): void
    {
        $this->artifact           = $this->createMock(Artifact::class);
        $this->user               = UserTestBuilder::buildWithDefaults();
        $this->tracker            = $this->createMock(Tracker::class);
        $incoming_message_factory = $this->createMock(Tracker_Artifact_MailGateway_IncomingMessageFactory::class);
        $this->artifact_creator   = $this->createMock(TrackerArtifactCreator::class);
        $this->tracker_config     = $this->createMock(MailGatewayConfig::class);
        $this->incoming_mail_dao  = $this->createMock(Tracker_Artifact_Changeset_IncomingMailDao::class);

        $citation_stripper = $this->createMock(Tracker_Artifact_MailGateway_CitationStripper::class);
        $citation_stripper->method('stripText')->with(self::BODY)->willReturn(self::STRIPPED_BODY);

        $this->tracker->method('getId')->willReturn(888);

        $this->incoming_message = $this->createMock(Tracker_Artifact_MailGateway_IncomingMessage::class);
        $this->incoming_message->method('getUser')->willReturn($this->user);
        $this->incoming_message->method('getArtifact')->willReturn($this->artifact);
        $this->incoming_message->method('getTracker')->willReturn($this->tracker);
        $this->incoming_message->method('getBody')->willReturn(self::BODY);

        $incoming_message_factory->method('build')->willReturn($this->incoming_message);

        $this->incoming_mail = $this->createMock(IncomingMail::class);
        $this->incoming_mail->method('getRawMail')->willReturn('Raw mail');

        $filter = $this->createMock(MailGatewayFilter::class);

        $notifier = $this->createStub(Tracker_Artifact_MailGateway_Notifier::class);
        $notifier->method('sendErrorMailTrackerGeneric');
        $notifier->method('sendErrorMailInsufficientPermissionUpdate');

        $this->mailgateway = new Tracker_Artifact_MailGateway_TokenMailGateway(
            $incoming_message_factory,
            $citation_stripper,
            $notifier,
            $this->incoming_mail_dao,
            $this->artifact_creator,
            $this->createMock(Tracker_FormElementFactory::class),
            new Tracker_ArtifactByEmailStatus($this->tracker_config),
            new NullLogger(),
            $filter
        );

        $filter->method('isAnAutoReplyMail')->willReturn(false);
    }

    public function testItDoesNotCreateArtifact(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(false);
        $this->artifact_creator->expects(self::never())->method('create');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesANewChangeset(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);
        $this->artifact->expects(self::once())->method('createNewChangeset')->with([], self::STRIPPED_BODY, $this->user, self::anything(), self::anything());

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesANewChangesetEvenIfPlatformIsInInsecureMode(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects(self::once())->method('createNewChangeset')->with([], self::STRIPPED_BODY, $this->user, self::anything(), self::anything());

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesNothingWhenGatewayIsDisabled(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotCreateWhenUserCannotUpdate(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('getId')->willReturn(101);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(false);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItUpdatesArtifact(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects(self::once())->method('createNewChangeset');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotUpdateArtifactWhenMailGatewayIsDisabled(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects(self::never())->method('createNewChangeset');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItLinksRawEmailToCreatedChangeset(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);
        $this->artifact->method('createNewChangeset')->willReturn(ChangesetTestBuilder::aChangeset(666)->build());

        $this->incoming_mail_dao->expects(self::once())->method('save')->with(666, 'Raw mail');

        $this->mailgateway->process($this->incoming_mail);
    }
}
