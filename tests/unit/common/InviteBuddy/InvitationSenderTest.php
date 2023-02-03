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
use Tuleap\ForgeConfigSandbox;
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
    /**
     * @var InvitationInstrumentation&MockObject
     */
    private $instrumentation;

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

        $this->sender = new InvitationSender(
            $this->gate_keeper,
            $this->email_notifier,
            $this->user_manager,
            $this->dao,
            $this->logger,
            $this->instrumentation,
            new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
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

        $this->sender->send($this->current_user, ["john@example.com"], null);
    }

    public function testItDoesNothingIfAllConditionsAreNotOk(): void
    {
        $this->gate_keeper
            ->method('checkNotificationsCanBeSent')
            ->willThrowException(new InvitationSenderGateKeeperException());

        $this->email_notifier->expects(self::never())->method("send");

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->sender->send($this->current_user, ["john@example.com"], null);
    }

    public function testItSendAnInvitationForEachEmailAndLogStatus(): void
    {
        $known_user = $this->createMock(\PFUser::class);
        $known_user->method('getId')->willReturn(1001);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(3);

        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager
            ->method('getUserByEmail')
            ->withConsecutive(
                ["john@example.com"],
                ["doe@example.com"],
            )
            ->willReturnOnConsecutiveCalls(null, $known_user);

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->withConsecutive(
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) {
                            return $recipient->user === null && $recipient->email === "john@example.com";
                        }
                    ),
                    "A custom message",
                    $this->anything(),
                ],
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) use ($known_user) {
                            return $recipient->user === $known_user && $recipient->email === "doe@example.com";
                        }
                    ),
                    "A custom message",
                    $this->anything(),
                ]
            )
            ->willReturnOnConsecutiveCalls(true, true);

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->anything(), 123, "john@example.com", null, "A custom message", $this->anything()],
                [$this->anything(), 123, "doe@example.com", 1001, "A custom message", $this->anything()]
            );

        $this->instrumentation
            ->expects(self::exactly(2))
            ->method('incrementPlatformInvitation');


        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsSent');

        self::assertEmpty(
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], "A custom message")
        );
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

        self::assertEmpty($this->sender->send($this->current_user, ["", null, "doe@example.com"], null));
    }

    public function testItReturnsEmailsInFailureAndLogStatus(): void
    {
        $this->gate_keeper->expects(self::once())->method('checkNotificationsCanBeSent');
        $this->user_manager->method('getUserByEmail')->willReturn(null);

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->email_notifier
            ->expects(self::exactly(2))
            ->method("send")
            ->withConsecutive(
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) {
                            return $recipient->user === null && $recipient->email === "john@example.com";
                        }
                    ),
                    null,
                    $this->anything(),
                ],
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) {
                            return $recipient->user === null && $recipient->email === "doe@example.com";
                        }
                    ),
                    null,
                    $this->anything(),
                ]
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->anything(), 123, "john@example.com", null, null, $this->anything()],
                [$this->anything(), 123, "doe@example.com", null, null, $this->anything()]
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
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null)
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
            ->withConsecutive(
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) {
                            return $recipient->user === null && $recipient->email === "john@example.com";
                        }
                    ),
                    null,
                    $this->anything(),
                ],
                [
                    $this->current_user,
                    $this->callback(
                        function (InvitationRecipient $recipient) {
                            return $recipient->user === null && $recipient->email === "doe@example.com";
                        }
                    ),
                    null,
                    $this->anything(),
                ]
            )
            ->willReturnOnConsecutiveCalls(false, false);

        $this->dao
            ->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive(
                [$this->anything(), 123, "john@example.com", null, null, $this->anything()],
                [$this->anything(), 123, "doe@example.com", null, null, $this->anything()]
            );

        $this->logger
            ->expects(self::exactly(2))
            ->method('error')
            ->withConsecutive(
                ["Unable to send invitation from user #123 to john@example.com"],
                ["Unable to send invitation from user #123 to doe@example.com"]
            );

        $this->dao
            ->expects(self::exactly(2))
            ->method('markAsError');

        $this->expectException(UnableToSendInvitationsException::class);

        $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null);
    }
}
