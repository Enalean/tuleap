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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\ForgeConfigSandbox;
use UserManager;

class InvitationSenderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvitationSenderGateKeeper
     */
    private $gate_keeper;
    /**
     * @var InvitationSender
     */
    private $sender;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $current_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvitationEmailNotifier
     */
    private $email_notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvitationDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvitationInstrumentation
     */
    private $instrumentation;

    protected function setUp(): void
    {
        $this->current_user = Mockery::mock(\PFUser::class);
        $this->current_user->shouldReceive(['getId' => 123]);

        $this->gate_keeper     = Mockery::mock(InvitationSenderGateKeeper::class);
        $this->email_notifier  = Mockery::mock(InvitationEmailNotifier::class);
        $this->user_manager    = Mockery::mock(UserManager::class);
        $this->dao             = Mockery::mock(InvitationDao::class);
        $this->logger          = Mockery::mock(LoggerInterface::class);
        $this->instrumentation = Mockery::mock(InvitationInstrumentation::class);

        $this->sender = new InvitationSender(
            $this->gate_keeper,
            $this->email_notifier,
            $this->user_manager,
            $this->dao,
            $this->logger,
            $this->instrumentation
        );

        ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 5);
    }

    public function testItEnsuresThatAllConditionsAreOkToSendInvitations(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();
        $this->user_manager->shouldReceive('getUserByEmail')->andReturnNull();

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->email_notifier->shouldReceive("send")->once()->andReturnTrue();
        $this->dao->shouldReceive('save');
        $this->instrumentation->shouldReceive('increment');

        $this->sender->send($this->current_user, ["john@example.com"], null);
    }

    public function testItDoesNothingIfAllConditionsAreNotOk(): void
    {
        $this->gate_keeper
            ->shouldReceive('checkNotificationsCanBeSent')
            ->andThrow(InvitationSenderGateKeeperException::class);

        $this->email_notifier->shouldReceive("send")->never();

        $this->expectException(InvitationSenderGateKeeperException::class);

        $this->sender->send($this->current_user, ["john@example.com"], null);
    }

    public function testItSendAnInvitationForEachEmailAndLogStatus(): void
    {
        $known_user = Mockery::mock(\PFUser::class);
        $known_user->shouldReceive(['getId' => 1001]);

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(3);

        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();
        $this->user_manager
            ->shouldReceive('getUserByEmail')
            ->with("john@example.com")
            ->andReturnNull();
        $this->user_manager
            ->shouldReceive('getUserByEmail')
            ->with("doe@example.com")
            ->andReturn($known_user);

        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "john@example.com";
                    }
                ),
                "A custom message"
            )
            ->once()
            ->andReturnTrue();
        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) use ($known_user) {
                        return $recipient->user === $known_user && $recipient->email === "doe@example.com";
                    }
                ),
                "A custom message"
            )
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "john@example.com", null, "A custom message", "sent")
            ->once();
        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "doe@example.com", 1001, "A custom message", "sent")
            ->once();

        $this->instrumentation
            ->shouldReceive('increment')
            ->twice();

        self::assertEmpty(
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], "A custom message")
        );
    }

    public function testItIgnoresEmptyEmails(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();
        $this->user_manager->shouldReceive('getUserByEmail')->andReturnNull();

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "doe@example.com";
                    }
                ),
                null
            )
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "doe@example.com", null, null, "sent")
            ->once();

        $this->instrumentation
            ->shouldReceive('increment')
            ->once();

        self::assertEmpty($this->sender->send($this->current_user, ["", null, "doe@example.com"], null));
    }

    public function testItReturnsEmailsInFailureAndLogStatus(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();
        $this->user_manager->shouldReceive('getUserByEmail')->andReturnNull();

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "john@example.com";
                    }
                ),
                null
            )
            ->once()
            ->andReturnFalse();
        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "doe@example.com";
                    }
                ),
                null
            )
            ->once()
            ->andReturnTrue();

        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "john@example.com", null, null, "error")
            ->once();
        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "doe@example.com", null, null, "sent")
            ->once();

        $this->instrumentation
            ->shouldReceive('increment')
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("Unable to send invitation from user #123 to john@example.com")
            ->once();

        self::assertEquals(
            ["john@example.com"],
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null)
        );
    }

    public function testItRaisesAnExceptionIfEveryEmailsAreInFailure(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();
        $this->user_manager->shouldReceive('getUserByEmail')->andReturnNull();

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "john@example.com";
                    }
                ),
                null
            )
            ->once()
            ->andReturnFalse();
        $this->email_notifier
            ->shouldReceive("send")
            ->with(
                $this->current_user,
                Mockery::on(
                    function (InvitationRecipient $recipient) {
                        return $recipient->user === null && $recipient->email === "doe@example.com";
                    }
                ),
                null
            )
            ->once()
            ->andReturnFalse();

        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "john@example.com", null, null, "error")
            ->once();
        $this->dao
            ->shouldReceive('save')
            ->with(Mockery::any(), 123, "doe@example.com", null, null, "error")
            ->once();

        $this->logger
            ->shouldReceive('error')
            ->with("Unable to send invitation from user #123 to john@example.com")
            ->once();
        $this->logger
            ->shouldReceive('error')
            ->with("Unable to send invitation from user #123 to doe@example.com")
            ->once();

        $this->expectException(UnableToSendInvitationsException::class);

        $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null);
    }
}
