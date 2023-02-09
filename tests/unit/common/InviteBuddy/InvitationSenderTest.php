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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class InvitationSenderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private InvitationSenderGateKeeper|MockObject $gate_keeper;
    private InvitationSender $sender;
    private MockObject|\PFUser $current_user;
    private InvitationEmailNotifier|MockObject $email_notifier;
    private UserManager|MockObject $user_manager;
    private InvitationDao|MockObject $dao;
    private LoggerInterface|MockObject $logger;
    private InvitationInstrumentation&MockObject $instrumentation;
    private MockObject&MembershipDelegationDao $delegation_dao;
    private \ProjectHistoryDao&MockObject $history_dao;

    protected function setUp(): void
    {
        $this->current_user = $this->createMock(\PFUser::class);
        $this->current_user->method('getId')->willReturn(123);

        $this->gate_keeper     = $this->createMock(InvitationSenderGateKeeper::class);
        $this->email_notifier  = $this->createMock(InvitationEmailNotifier::class);
        $this->user_manager    = $this->createMock(UserManager::class);
        $this->dao             = $this->createMock(InvitationDao::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->instrumentation = $this->createMock(InvitationInstrumentation::class);
        $this->delegation_dao  = $this->createMock(MembershipDelegationDao::class);
        $this->history_dao     = $this->createMock(\ProjectHistoryDao::class);

        $this->sender = new InvitationSender(
            $this->gate_keeper,
            $this->email_notifier,
            $this->user_manager,
            $this->dao,
            $this->logger,
            $this->instrumentation,
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
            $this->delegation_dao,
            $this->history_dao,
        );

        ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 5);
    }

    public function testItEnsuresThatAllConditionsAreOkToSendInvitations(): void
    {
        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager->method('getUserByEmail')->willReturn(null);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->email_notifier->expects(self::once())->method("send")->willReturn(true);
        $this->dao->method('create');
        $this->dao->method('markAsSent');
        $this->instrumentation->method('incrementPlatformInvitation');

        $this->sender->send($this->current_user, ["john@example.com"], null, null, false);
    }

    public function testItDoesNothingIfAllConditionsAreNotOk(): void
    {
        $this->gate_keeper
            ->method('checkNotificationsCanBeSent')
            ->willThrowException(new InvitationSenderGateKeeperException());

        $this->email_notifier->expects(self::never())->method("send");

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->sender->send($this->current_user, ["john@example.com"], null, null, false, false);
    }

    public function testItSendAnInvitationForEachEmailAndLogStatus(): void
    {
        $known_user = $this->createMock(\PFUser::class);
        $known_user->method('getId')->willReturn(1001);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(3);

        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager
            ->method('getUserByEmail')
            ->willReturnCallback(
                fn (string $email): ?\PFUser => match ($email) {
                    "john@example.com" => null,
                    "doe@example.com" => $known_user,
                }
            );

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->willReturnCallback(
                fn(
                    \PFUser $current_user,
                    InvitationRecipient $recipient,
                    ?string $custom_message,
                    ConcealedString $token,
                    ?\Project $project,
                ): bool => match (true) {
                    $current_user === $this->current_user && $custom_message === "A custom message" &&
                    (
                        ($recipient->user === null && $recipient->email === "john@example.com") ||
                        ($recipient->user === $known_user && $recipient->email === "doe@example.com")
                    ) => true,
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                fn (
                    int $created_on,
                    int $from_user_id,
                    string $to_email,
                    ?int $to_user_id,
                    ?int $to_project_id,
                    ?string $custom_message,
                    SplitTokenVerificationString $verifier,
                ): int => match (true) {
                    $from_user_id === 123 && $custom_message === "A custom message" &&
                    (
                        ($to_email === "john@example.com" && $to_user_id === null) ||
                        ($to_email === "doe@example.com" && $to_user_id === 1001)
                    ) => 1
                }
            );

        $this->instrumentation
            ->expects(self::exactly(2))
            ->method('incrementPlatformInvitation');


        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsSent');

        self::assertEmpty(
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null, "A custom message", false)
        );
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

        $known_user = $this->createMock(\PFUser::class);
        $known_user->method('getId')->willReturn(1001);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(3);

        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager
            ->method('getUserByEmail')
            ->willReturnCallback(
                fn (string $email): ?\PFUser => match ($email) {
                    "john@example.com" => null,
                    "doe@example.com" => $known_user,
                }
            );

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->willReturnCallback(
                fn(
                    \PFUser $user,
                    InvitationRecipient $recipient,
                    ?string $custom_message,
                    ConcealedString $token,
                    ?\Project $project,
                ): bool => match (true) {
                    $user === $current_user && $custom_message === "A custom message" &&
                    (
                        ($recipient->user === null && $recipient->email === "john@example.com") ||
                        ($recipient->user === $known_user && $recipient->email === "doe@example.com")
                    ) => true,
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                fn (
                    int $created_on,
                    int $from_user_id,
                    string $to_email,
                    ?int $to_user_id,
                    ?int $to_project_id,
                    ?string $custom_message,
                    SplitTokenVerificationString $verifier,
                ): int => match (true) {
                    $from_user_id === 123 && $custom_message === "A custom message" &&
                    (
                        ($to_email === "john@example.com" && $to_user_id === null) ||
                        ($to_email === "doe@example.com" && $to_user_id === 1001)
                    ) => 1
                }
            );

        $this->instrumentation
            ->expects(self::exactly(2))
            ->method('incrementProjectInvitation');
        $this->history_dao
            ->expects(self::exactly(2))
            ->method('addHistory');


        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsSent');

        self::assertEmpty(
            $this->sender->send($current_user, ["john@example.com", "doe@example.com"], $project, "A custom message", false)
        );
    }

    public function testItSendAnInvitationForAProjectIfUserHasDelegation(): void
    {
        $project_id = 111;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        $this->delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(true);

        $known_user = $this->createMock(\PFUser::class);
        $known_user->method('getId')->willReturn(1001);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(3);

        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager
            ->method('getUserByEmail')
            ->willReturnCallback(
                fn (string $email): ?\PFUser => match ($email) {
                    "john@example.com" => null,
                    "doe@example.com" => $known_user,
                }
            );

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->willReturnCallback(
                fn(
                    \PFUser $user,
                    InvitationRecipient $recipient,
                    ?string $custom_message,
                    ConcealedString $token,
                    ?\Project $project,
                ): bool => match (true) {
                    $user === $current_user && $custom_message === "A custom message" &&
                    (
                        ($recipient->user === null && $recipient->email === "john@example.com") ||
                        ($recipient->user === $known_user && $recipient->email === "doe@example.com")
                    ) => true,
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                fn (
                    int $created_on,
                    int $from_user_id,
                    string $to_email,
                    ?int $to_user_id,
                    ?int $to_project_id,
                    ?string $custom_message,
                    SplitTokenVerificationString $verifier,
                ): int => match (true) {
                    $from_user_id === 123 && $custom_message === "A custom message" &&
                    (
                        ($to_email === "john@example.com" && $to_user_id === null) ||
                        ($to_email === "doe@example.com" && $to_user_id === 1001)
                    ) => 1
                }
            );

        $this->instrumentation
            ->expects(self::exactly(2))
            ->method('incrementProjectInvitation');
        $this->history_dao
            ->expects(self::exactly(2))
            ->method('addHistory');

        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsSent');

        self::assertEmpty(
            $this->sender->send($current_user, ["john@example.com", "doe@example.com"], $project, "A custom message", false)
        );
    }

    public function testExceptionWhenInvitationForAProjectAndUserIsNotProjectAdminAndHasNoDelegation(): void
    {
        $project_id = 111;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $current_user = UserTestBuilder::aUser()
            ->withId(123)
            ->withoutSiteAdministrator()
            ->withMemberOf($project)
            ->build();

        $this->delegation_dao->method('doesUserHasMembershipDelegation')->willReturn(false);

        $this->email_notifier
            ->expects(self::never())
            ->method("send");

        $this->dao
            ->expects(self::never())
            ->method('create');

        $this->expectException(MustBeProjectAdminToInvitePeopleInProjectException::class);

        $this->sender->send($current_user, ["john@example.com", "doe@example.com"], $project, "A custom message", false);
    }

    public function testItIgnoresEmptyEmails(): void
    {
        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager->method('getUserByEmail')->willReturn(null);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->email_notifier
            ->expects(self::once())
            ->method("send")
            ->with(
                $this->current_user,
                $this->callback(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "doe@example.com";
                    }
                ),
                null,
                $this->anything(),
            )
            ->willReturn(true);

        $this->dao
            ->expects(self::once())
            ->method('create')
            ->with($this->anything(), 123, "doe@example.com", null, null, $this->anything());

        $this->dao
            ->expects(self::once())
            ->method('markAsSent');

        $this->instrumentation
            ->expects(self::once())
            ->method('incrementPlatformInvitation');

        self::assertEmpty($this->sender->send($this->current_user, ["", null, "doe@example.com"], null, null, false));
    }

    public function testItReturnsEmailsInFailureAndLogStatus(): void
    {
        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager->method('getUserByEmail')->willReturn(null);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->willReturnCallback(
                fn(
                    \PFUser $current_user,
                    InvitationRecipient $recipient,
                    ?string $custom_message,
                    ConcealedString $token,
                    ?\Project $project,
                ): bool => match (true) {
                    $current_user === $this->current_user && $custom_message === null &&
                        $recipient->user === null && $recipient->email === "john@example.com" => false,
                    $current_user === $this->current_user && $custom_message === null &&
                        $recipient->user === null && $recipient->email === "doe@example.com" => true,
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                fn (
                    int $created_on,
                    int $from_user_id,
                    string $to_email,
                    ?int $to_user_id,
                    ?int $to_project_id,
                    ?string $custom_message,
                    SplitTokenVerificationString $verifier,
                ): int => match (true) {
                    $from_user_id === 123 && $custom_message === null &&
                    (
                        ($to_email === "john@example.com" && $to_user_id === null) ||
                        ($to_email === "doe@example.com" && $to_user_id === null)
                    ) => 1
                }
            );

        $this->instrumentation
            ->expects(self::once())
            ->method('incrementPlatformInvitation');
        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with("Unable to send invitation from user #123 to john@example.com");

        $this->dao->expects(self::once())->method('markAsError');
        $this->dao->expects(self::once())->method('markAsSent');

        self::assertEquals(
            ["john@example.com"],
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null, null, false)
        );
    }

    public function testItRaisesAnExceptionIfEveryEmailsAreInFailure(): void
    {
        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager->method('getUserByEmail')->willReturn(null);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->willReturnCallback(
                fn(
                    \PFUser $user,
                    InvitationRecipient $recipient,
                    ?string $custom_message,
                    ConcealedString $token,
                    ?\Project $project,
                ): bool => match (true) {
                    $user === $this->current_user && $custom_message === null && $recipient->user === null &&
                    ($recipient->email === "john@example.com" || $recipient->email === "doe@example.com") => false
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(
                fn (
                    int $created_on,
                    int $from_user_id,
                    string $to_email,
                    ?int $to_user_id,
                    ?int $to_project_id,
                    ?string $custom_message,
                    SplitTokenVerificationString $verifier,
                ): int => match (true) {
                    $from_user_id === 123 && $custom_message === null && $to_user_id === null &&
                        ($to_email === "john@example.com" || $to_email === "doe@example.com") => 1
                }
            );

        $this->logger
            ->expects(self::exactly(2))
            ->method('error')
            ->willReturnCallback(
                function (string $message): void {
                    match ($message) {
                        "Unable to send invitation from user #123 to john@example.com",
                        "Unable to send invitation from user #123 to doe@example.com" => true
                    };
                }
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsError');

        $this->expectException(UnableToSendInvitationsException::class);

        $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null, null, false);
    }
}
