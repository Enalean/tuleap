<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy;

use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvitationToOneRecipientWithoutVerificationSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItSendsAnInvitationNotInAProject(): void
    {
        $email_notifier = $this->createMock(InvitationEmailNotifier::class);
        $email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(true);

        $instrumentation = $this->createMock(InvitationInstrumentation::class);
        $instrumentation
            ->expects(self::once())
            ->method('incrementPlatformInvitation');
        $instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $history_dao = $this->createMock(\ProjectHistoryDao::class);
        $history_dao
            ->expects(self::never())
            ->method('addHistory');

        $invitation_creator        = InvitationCreatorStub::buildSelf();
        $invitation_status_updater = InvitationStatusUpdaterStub::buildSelf();

        $sender = $this->buildInvitationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            $history_dao,
        );

        $result = $sender->sendToRecipient(
            UserTestBuilder::buildWithDefaults(),
            new InvitationRecipient(null, 'alice@example.com'),
            null,
            'custom message',
            null,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($invitation_creator->hasBeenCreated());
        self::assertTrue($invitation_status_updater->hasBeenMarkedAsSent());
        self::assertFalse($invitation_status_updater->hasBeenMarkedAsError());
    }

    public function testItFailsToSendAnInvitationNotInAProject(): void
    {
        $email_notifier = $this->createMock(InvitationEmailNotifier::class);
        $email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(false);

        $instrumentation = $this->createMock(InvitationInstrumentation::class);
        $instrumentation
            ->expects(self::never())
            ->method('incrementPlatformInvitation');
        $instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $history_dao = $this->createMock(\ProjectHistoryDao::class);
        $history_dao
            ->expects(self::never())
            ->method('addHistory');

        $invitation_creator        = InvitationCreatorStub::buildSelf();
        $invitation_status_updater = InvitationStatusUpdaterStub::buildSelf();

        $sender = $this->buildInvitationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            $history_dao,
        );

        $result = $sender->sendToRecipient(
            UserTestBuilder::buildWithDefaults(),
            new InvitationRecipient(null, 'alice@example.com'),
            null,
            'custom message',
            null,
        );

        self::assertTrue(Result::isErr($result));
        self::assertTrue($invitation_creator->hasBeenCreated());
        self::assertFalse($invitation_status_updater->hasBeenMarkedAsSent());
        self::assertTrue($invitation_status_updater->hasBeenMarkedAsError());
    }

    public function testItSendsAnInvitationInAProject(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $from_user = UserTestBuilder::buildWithDefaults();

        $email_notifier = $this->createMock(InvitationEmailNotifier::class);
        $email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(true);

        $instrumentation = $this->createMock(InvitationInstrumentation::class);
        $instrumentation
            ->expects(self::never())
            ->method('incrementPlatformInvitation');
        $instrumentation
            ->expects(self::once())
            ->method('incrementProjectInvitation');

        $history_dao = $this->createMock(\ProjectHistoryDao::class);
        $history_dao
            ->expects(self::once())
            ->method('addHistory')
            ->with(
                $project,
                $from_user,
                self::anything(),
                InvitationHistoryEntry::InvitationSent->value,
                '1',
                [],
            );

        $invitation_creator        = InvitationCreatorStub::buildSelf();
        $invitation_status_updater = InvitationStatusUpdaterStub::buildSelf();

        $sender = $this->buildInvitationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            $history_dao,
        );

        $result = $sender->sendToRecipient(
            $from_user,
            new InvitationRecipient(null, 'alice@example.com'),
            $project,
            'custom message',
            null,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($invitation_creator->hasBeenCreated());
        self::assertTrue($invitation_status_updater->hasBeenMarkedAsSent());
        self::assertFalse($invitation_status_updater->hasBeenMarkedAsError());
    }

    public function testItResendsAnInvitationInAProject(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $from_user = UserTestBuilder::buildWithDefaults();

        $email_notifier = $this->createMock(InvitationEmailNotifier::class);
        $email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(true);

        $instrumentation = $this->createMock(InvitationInstrumentation::class);
        $instrumentation
            ->expects(self::never())
            ->method('incrementPlatformInvitation');
        $instrumentation
            ->expects(self::once())
            ->method('incrementProjectInvitation');

        $history_dao = $this->createMock(\ProjectHistoryDao::class);
        $history_dao
            ->expects(self::once())
            ->method('addHistory')
            ->with(
                $project,
                $from_user,
                self::anything(),
                InvitationHistoryEntry::InvitationResent->value,
                '1',
                [],
            );

        $invitation_creator        = InvitationCreatorStub::buildSelf();
        $invitation_status_updater = InvitationStatusUpdaterStub::buildSelf();

        $sender = $this->buildInvitationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            $history_dao,
        );

        $result = $sender->sendToRecipient(
            $from_user,
            new InvitationRecipient(null, 'alice@example.com'),
            $project,
            'custom message',
            $from_user,
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($invitation_creator->hasBeenCreated());
        self::assertTrue($invitation_status_updater->hasBeenMarkedAsSent());
        self::assertFalse($invitation_status_updater->hasBeenMarkedAsError());
    }

    public function testItFailsToSendAnInvitationInAProject(): void
    {
        $project   = ProjectTestBuilder::aProject()->build();
        $from_user = UserTestBuilder::buildWithDefaults();

        $email_notifier = $this->createMock(InvitationEmailNotifier::class);
        $email_notifier
            ->expects(self::once())
            ->method('send')
            ->willReturn(false);

        $instrumentation = $this->createMock(InvitationInstrumentation::class);
        $instrumentation
            ->expects(self::never())
            ->method('incrementPlatformInvitation');
        $instrumentation
            ->expects(self::never())
            ->method('incrementProjectInvitation');

        $history_dao = $this->createMock(\ProjectHistoryDao::class);
        $history_dao
            ->expects(self::never())
            ->method('addHistory');

        $invitation_creator        = InvitationCreatorStub::buildSelf();
        $invitation_status_updater = InvitationStatusUpdaterStub::buildSelf();

        $sender = $this->buildInvitationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            $history_dao,
        );

        $result = $sender->sendToRecipient(
            UserTestBuilder::buildWithDefaults(),
            new InvitationRecipient(null, 'alice@example.com'),
            $project,
            'custom message',
            null,
        );

        self::assertTrue(Result::isErr($result));
        self::assertTrue($invitation_creator->hasBeenCreated());
        self::assertFalse($invitation_status_updater->hasBeenMarkedAsSent());
        self::assertTrue($invitation_status_updater->hasBeenMarkedAsError());
    }

    private function buildInvitationSender(
        InvitationEmailNotifier $email_notifier,
        InvitationCreator $invitation_creator,
        InvitationStatusUpdater $invitation_status_updater,
        InvitationInstrumentation $instrumentation,
        \ProjectHistoryDao $history_dao,
    ): InvitationToOneRecipientWithoutVerificationSender {
        return new InvitationToOneRecipientWithoutVerificationSender(
            $email_notifier,
            $invitation_creator,
            $invitation_status_updater,
            $instrumentation,
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            $history_dao,
        );
    }
}
