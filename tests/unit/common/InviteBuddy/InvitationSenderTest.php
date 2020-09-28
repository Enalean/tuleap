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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class InvitationSenderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        $this->current_user = Mockery::mock(\PFUser::class);

        $this->gate_keeper    = Mockery::mock(InvitationSenderGateKeeper::class);
        $this->email_notifier = Mockery::mock(InvitationEmailNotifier::class);
        $this->sender         = new InvitationSender($this->gate_keeper, $this->email_notifier);
    }

    public function testItEnsuresThatAllConditionsAreOkToSendInvitations(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();

        $this->email_notifier->shouldReceive("send")->once()->andReturnTrue();

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

    public function testItSendAnInvitationForEachEmail(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();

        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "john@example.com", "A custom message")
            ->once()
            ->andReturnTrue();
        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "doe@example.com", "A custom message")
            ->once()
            ->andReturnTrue();

        self::assertEmpty($this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], "A custom message"));
    }

    public function testItIgnoresEmptyEmails(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();

        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "doe@example.com", null)
            ->once()
            ->andReturnTrue();

        self::assertEmpty($this->sender->send($this->current_user, ["", null, "doe@example.com"], null));
    }

    public function testItReturnsEmailsInFailure(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();

        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "john@example.com", null)
            ->once()
            ->andReturnFalse();
        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "doe@example.com", null)
            ->once()
            ->andReturnTrue();

        self::assertEquals(
            ["john@example.com"],
            $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null)
        );
    }

    public function testItRaisesAnExceptionIfEveryEmailsAreInFailure(): void
    {
        $this->gate_keeper->shouldReceive('checkNotificationsCanBeSent')->once();

        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "john@example.com", null)
            ->once()
            ->andReturnFalse();
        $this->email_notifier
            ->shouldReceive("send")
            ->with($this->current_user, "doe@example.com", null)
            ->once()
            ->andReturnFalse();

        $this->expectException(UnableToSendInvitationsException::class);

        $this->sender->send($this->current_user, ["john@example.com", "doe@example.com"], null);
    }
}
