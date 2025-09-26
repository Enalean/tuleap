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
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_IncomingMailDao;
use Tracker_Artifact_MailGateway_CitationStripper;
use Tracker_Artifact_MailGateway_IncomingMessage;
use Tracker_Artifact_MailGateway_IncomingMessageFactory;
use Tracker_Artifact_MailGateway_InsecureMailGateway;
use Tracker_Artifact_MailGateway_Notifier;
use Tracker_ArtifactByEmailStatus;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MailGatewayInsecureTest extends TestCase
{
    private const BODY          = 'justaucorps';
    private const STRIPPED_BODY = 'stripped justaucorps';

    private PFUser $user;
    private Tracker_Artifact_MailGateway_InsecureMailGateway $mailgateway;
    private Artifact&MockObject $artifact;
    private Tracker_Artifact_Changeset_IncomingMailDao&MockObject $incoming_mail_dao;
    private MailGatewayConfig&MockObject $tracker_config;
    private Tracker&MockObject $tracker;
    private Tracker_Artifact_MailGateway_IncomingMessage&MockObject $incoming_message;
    private IncomingMail&MockObject $incoming_mail;
    private Tracker_FormElementFactory&MockObject $formelement_factory;
    private Tracker_Artifact_Changeset $changeset;
    private TrackerArtifactCreator&MockObject $artifact_creator;

    #[\Override]
    protected function setUp(): void
    {
        $this->artifact            = $this->createMock(Artifact::class);
        $this->user                = UserTestBuilder::buildWithDefaults();
        $this->tracker             = $this->createMock(Tracker::class);
        $incoming_message_factory  = $this->createMock(Tracker_Artifact_MailGateway_IncomingMessageFactory::class);
        $this->artifact_creator    = $this->createMock(TrackerArtifactCreator::class);
        $this->formelement_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->tracker_config      = $this->createMock(MailGatewayConfig::class);
        $this->incoming_mail_dao   = $this->createMock(Tracker_Artifact_Changeset_IncomingMailDao::class);

        $this->tracker->method('getId')->willReturn(888);

        $citation_stripper = $this->createMock(Tracker_Artifact_MailGateway_CitationStripper::class);
        $citation_stripper->method('stripText')->with(self::BODY)->willReturn(self::STRIPPED_BODY);

        $this->incoming_message = $this->createMock(Tracker_Artifact_MailGateway_IncomingMessage::class);
        $this->incoming_message->method('getSubject')->willReturn('subject');
        $this->incoming_message->method('getUser')->willReturn($this->user);
        $this->incoming_message->method('getArtifact')->willReturn($this->artifact);
        $this->incoming_message->method('getTracker')->willReturn($this->tracker);
        $this->incoming_message->method('getBody')->willReturn(self::BODY);

        $incoming_message_factory->method('build')->willReturn($this->incoming_message);

        $this->incoming_mail = $this->createMock(IncomingMail::class);
        $this->incoming_mail->method('getRawMail')->willReturn('Raw mail');

        $title_field                = StringFieldBuilder::aStringField(452)->inTracker($this->tracker)->build();
        $description_field          = TextFieldBuilder::aTextField(854)->inTracker($this->tracker)->build();
        $retrieve_description_field = RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($description_field);

        $this->tracker->method('getTitleField')->willReturn($title_field);
        $this->tracker->method('getFormElementFields')->willReturn([$title_field, $description_field]);

        $this->changeset = ChangesetTestBuilder::aChangeset(666)->build();
        $filter          = $this->createMock(MailGatewayFilter::class);

        $notifier = $this->createStub(Tracker_Artifact_MailGateway_Notifier::class);
        $notifier->method('sendErrorMailTrackerGeneric');
        $notifier->method('sendErrorMailInsufficientPermissionUpdate');

        $this->mailgateway = new Tracker_Artifact_MailGateway_InsecureMailGateway(
            $incoming_message_factory,
            $citation_stripper,
            $notifier,
            $this->incoming_mail_dao,
            $this->artifact_creator,
            $this->formelement_factory,
            new Tracker_ArtifactByEmailStatus($this->tracker_config, $retrieve_description_field),
            new NullLogger(),
            $filter,
            $retrieve_description_field,
        );
        $filter->method('isAnAutoReplyMail')->willReturn(false);
    }

    public function testItUpdatesArtifact(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects($this->once())->method('createNewChangeset')->with([], self::STRIPPED_BODY, $this->user, self::anything(), self::anything());

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotUpdatesArtifactWhenGatewayIsDisabled(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(false);
        $this->incoming_message->method('isAFollowUp')->willReturn(true);
        $this->artifact->method('userCanUpdate')->with($this->user)->willReturn(true);

        $this->artifact->expects($this->never())->method('createNewChangeset');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItCreatesArtifact(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(false);
        $this->tracker->method('userCanSubmitArtifact')->willReturn(true);
        $this->formelement_factory->method('getUsedFieldsWithDefaultValue')->willReturn([]);

        $this->artifact_creator->expects($this->once())->method('create');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItUsesDefaultValuesForFields(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(false);
        $this->tracker->method('userCanSubmitArtifact')->willReturn(true);
        $this->artifact_creator->method('create');

        $this->formelement_factory->expects($this->once())->method('getUsedFieldsWithDefaultValue')->with($this->tracker, self::anything(), $this->user);

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItDoesNotCreateArtifactWhenGatewayIsDisabled(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(false);
        $this->incoming_message->method('isAFollowUp')->willReturn(false);
        $this->tracker->method('userCanSubmitArtifact')->willReturn(true);

        $this->artifact_creator->expects($this->never())->method('create');

        $this->mailgateway->process($this->incoming_mail);
    }

    public function testItLinksRawEmailToCreatedChangeset(): void
    {
        $this->tracker_config->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_config->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $artifact = ArtifactTestBuilder::anArtifact(56)->inTracker($this->tracker)->withChangesets($this->changeset)->build();

        $this->tracker->method('getItemName')->willReturn('item');
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->incoming_message->method('isAFollowUp')->willReturn(false);
        $this->artifact_creator->method('create')->willReturn($artifact);
        $this->tracker->method('userCanSubmitArtifact')->willReturn(true);
        $this->artifact_creator->method('create');
        $this->formelement_factory->method('getUsedFieldsWithDefaultValue')->willReturn([]);

        $this->incoming_mail_dao->expects($this->once())->method('save')->with(666, 'Raw mail');

        $this->mailgateway->process($this->incoming_mail);
    }
}
