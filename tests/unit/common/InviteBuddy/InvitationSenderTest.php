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

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\Test\TestLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembersStub;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByEmailStub;

final class InvitationSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 5);
    }

    public function testItEnsuresThatAllConditionsAreOkToSendInvitations(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );
        $sender->send($current_user, ["john@example.com"], null, null, null);
        self::assertTrue($one_recipient_sender->hasBeenCalled());
    }

    public function testItDoesNothingIfAllConditionsAreNotOk(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper
            ->method('checkNotificationsCanBeSent')
            ->willThrowException(new InvitationSenderGateKeeperException());

        $this->expectException(InvitationSenderGateKeeperException::class);

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );
        $sender->send($current_user, ["john@example.com"], null, null, null);
        self::assertFalse($one_recipient_sender->hasBeenCalled());
    }

    public function testItSendAnInvitationForEachEmailAndLogStatus(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $known_user = UserTestBuilder::aUser()
            ->withId(1001)
            ->withEmail('doe@example.com')
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withUser($known_user),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );

        self::assertEmpty(
            $sender->send($current_user, ["john@example.com", "doe@example.com"], null, "A custom message", null)
        );

        $calls = $one_recipient_sender->getCalls();
        self::assertCount(2, $calls);

        self::assertSame($current_user, $calls[0]['from_user']);
        self::assertEquals('john@example.com', $calls[0]['recipient']->email);
        self::assertNull($calls[0]['recipient']->user);
        self::assertNull($calls[0]['project']);
        self::assertEquals('A custom message', $calls[0]['custom_message']);
        self::assertNull($calls[0]['resent_from_user']);

        self::assertSame($current_user, $calls[1]['from_user']);
        self::assertEquals('doe@example.com', $calls[1]['recipient']->email);
        self::assertSame($known_user, $calls[1]['recipient']->user);
        self::assertNull($calls[1]['project']);
        self::assertEquals('A custom message', $calls[1]['custom_message']);
        self::assertNull($calls[1]['resent_from_user']);
    }

    public function testItSendAnInvitationForAProjectIfUserIsProjectAdmin(): void
    {
        $project_id = 111;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($project)
            ->build();

        $known_user = UserTestBuilder::aUser()
            ->withId(1001)
            ->withEmail('doe@example.com')
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withUser($known_user),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::canManageMembers(),
            $one_recipient_sender,
        );

        self::assertEmpty(
            $sender->send($current_user, ["john@example.com", "doe@example.com"], $project, "A custom message", null)
        );

        $calls = $one_recipient_sender->getCalls();
        self::assertCount(2, $calls);

        self::assertSame($current_user, $calls[0]['from_user']);
        self::assertEquals('john@example.com', $calls[0]['recipient']->email);
        self::assertNull($calls[0]['recipient']->user);
        self::assertSame($project, $calls[0]['project']);
        self::assertEquals('A custom message', $calls[0]['custom_message']);
        self::assertNull($calls[0]['resent_from_user']);

        self::assertSame($current_user, $calls[1]['from_user']);
        self::assertEquals('doe@example.com', $calls[1]['recipient']->email);
        self::assertSame($known_user, $calls[1]['recipient']->user);
        self::assertSame($project, $calls[1]['project']);
        self::assertEquals('A custom message', $calls[1]['custom_message']);
        self::assertNull($calls[1]['resent_from_user']);
    }

    public function testExceptionWhenInvitationForAProjectAndUserIsNotProjectAdminAndHasNoDelegation(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $project_id = 111;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);

        $this->expectException(UserIsNotAllowedToManageProjectMembersException::class);

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );
        $sender->send($current_user, ["john@example.com", "doe@example.com"], $project, "A custom message", null);
        self::assertFalse($one_recipient_sender->hasBeenCalled());
    }

    public function testItIgnoresEmptyEmails(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withOk();

        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            new TestLogger(),
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );
        self::assertEmpty(
            $sender->send($current_user, ["", null, "doe@example.com"], null, null, null)
        );

        $calls = $one_recipient_sender->getCalls();
        self::assertCount(1, $calls);

        self::assertSame($current_user, $calls[0]['from_user']);
        self::assertEquals('doe@example.com', $calls[0]['recipient']->email);
        self::assertNull($calls[0]['recipient']->user);
        self::assertNull($calls[0]['project']);
        self::assertNull($calls[0]['custom_message']);
        self::assertNull($calls[0]['resent_from_user']);
    }

    public function testItReturnsEmailsInFailureAndLogStatus(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();

        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withReturnCallback(
            fn(
                \PFUser $from_user,
                InvitationRecipient $recipient,
                ?\Project $project,
                ?string $custom_message,
                ?\PFUser $resent_from_user,
            ): Ok|Err => match (true) {
                $current_user === $from_user && $custom_message === null &&
                    $recipient->user === null && $recipient->email === "john@example.com" => Result::err(Fault::fromMessage("Unable to send invitation from user #123 to john@example.com")),
                $current_user === $from_user && $custom_message === null &&
                    $recipient->user === null && $recipient->email === "doe@example.com" => Result::ok(true),
            }
        );

        $logger = new TestLogger();
        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            $logger,
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );

        self::assertEquals(
            ["john@example.com"],
            $sender->send($current_user, ["john@example.com", "doe@example.com"], null, null, null)
        );
        self::assertTrue(
            $logger->hasError("Unable to send invitation from user #123 to john@example.com")
        );
        self::assertCount(2, $one_recipient_sender->getCalls());
    }

    public function testItRaisesAnExceptionIfEveryEmailsAreInFailure(): void
    {
        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->build();


        $gate_keeper = $this->createMock(InvitationSenderGateKeeper::class);
        $gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');

        $this->expectException(UnableToSendInvitationsException::class);

        $one_recipient_sender = InvitationToOneRecipientSenderStub::withReturnCallback(
            fn(
                \PFUser $from_user,
                InvitationRecipient $recipient,
                ?\Project $project,
                ?string $custom_message,
                ?\PFUser $resent_from_user,
            ): Ok|Err => match (true) {
                $current_user === $from_user && $custom_message === null &&
                $recipient->user === null && $recipient->email === "john@example.com" => Result::err(Fault::fromMessage("Unable to send invitation from user #123 to john@example.com")),
                $current_user === $from_user && $custom_message === null &&
                $recipient->user === null && $recipient->email === "doe@example.com" => Result::err(Fault::fromMessage("Unable to send invitation from user #123 to doe@example.com")),
            }
        );

        $logger = new TestLogger();
        $sender = new InvitationSender(
            $gate_keeper,
            RetrieveUserByEmailStub::withNoUser(),
            $logger,
            EnsureUserCanManageProjectMembersStub::cannotManageMembers(),
            $one_recipient_sender,
        );
        $sender->send($current_user, ["john@example.com", "doe@example.com"], null, null, null);

        self::assertTrue(
            $logger->hasError("Unable to send invitation from user #123 to john@example.com")
        );
        self::assertTrue(
            $logger->hasError("Unable to send invitation from user #123 to doe@example.com")
        );
        self::assertCount(2, $one_recipient_sender->getCalls());
    }
}
