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
use Psr\Log\LoggerInterface;
use UserManager;

class AccountCreationFeedbackTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|InvitationDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccountCreationFeedbackEmailNotifier
     */
    private $email_notifier;
    /**
     * @var AccountCreationFeedback
     */
    private $account_creation_feedback;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;

    protected function setUp(): void
    {
        $this->logger         = Mockery::mock(LoggerInterface::class);
        $this->dao            = Mockery::mock(InvitationDao::class);
        $this->email_notifier = Mockery::mock(AccountCreationFeedbackEmailNotifier::class);
        $this->user_manager   = Mockery::mock(UserManager::class);

        $this->account_creation_feedback = new AccountCreationFeedback(
            $this->dao,
            $this->user_manager,
            $this->email_notifier,
            $this->logger,
        );
    }

    public function testItUpdatesInvitationsWithJustCreatedUser(): void
    {
        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation')
            ->with('doe@example.com', 104)
            ->once();

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn([]);

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }

    public function testItNotifiesNobodyIfUserWasNotInvited(): void
    {
        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn([]);

        $this->email_notifier
            ->shouldReceive('send')
            ->never();

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }

    public function testItNotifiesEveryPeopleWhoInvitedTheUser(): void
    {
        $from_user = Mockery::mock(\PFUser::class);
        $from_user->shouldReceive(['isAlive' => true]);
        $from_another_user = Mockery::mock(\PFUser::class);
        $from_another_user->shouldReceive(['isAlive' => true]);

        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                    [
                        'from_user_id' => 104,
                    ],
                ]
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(103)
            ->once()
            ->andReturn($from_user);

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(104)
            ->once()
            ->andReturn($from_another_user);

        $this->email_notifier
            ->shouldReceive('send')
            ->with($from_user, $new_user)
            ->once()
            ->andReturnTrue();
        $this->email_notifier
            ->shouldReceive('send')
            ->with($from_another_user, $new_user)
            ->once()
            ->andReturnTrue();

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }

    public function testItIgnoresUsersThatCannotBeFoundButLogsAnError(): void
    {
        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(103)
            ->once()
            ->andReturnNull();

        $this->email_notifier
            ->shouldReceive('send')
            ->never();

        $this->logger
            ->shouldReceive('error')
            ->with("Invitation was referencing an unknown user #103")
            ->once();

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }

    public function testItIgnoresUsersThatAreNotAliveButLogsAWarning(): void
    {
        $from_user = Mockery::mock(\PFUser::class);
        $from_user->shouldReceive(['isAlive' => false]);

        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(103)
            ->once()
            ->andReturn($from_user);

        $this->email_notifier
            ->shouldReceive('send')
            ->never();

        $this->logger
            ->shouldReceive('warning')
            ->with("Cannot send invitation feedback to inactive user #103")
            ->once();

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }

    public function testItLogsAnErrorIfEmailCannotBeSent(): void
    {
        $from_user = Mockery::mock(\PFUser::class);
        $from_user->shouldReceive(['isAlive' => true, 'getId' => 103]);

        $new_user = Mockery::mock(\PFUser::class);
        $new_user->shouldReceive(['getEmail' => 'doe@example.com', 'getId' => 104]);

        $this->dao
            ->shouldReceive('saveJustCreatedUserThanksToInvitation');

        $this->dao
            ->shouldReceive('searchByEmail')
            ->with('doe@example.com')
            ->once()
            ->andReturn(
                [
                    [
                        'from_user_id' => 103,
                    ],
                ]
            );

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(103)
            ->once()
            ->andReturn($from_user);

        $this->email_notifier
            ->shouldReceive('send')
            ->once()
            ->andReturnFalse();

        $this->logger
            ->shouldReceive('error')
            ->with("Unable to send invitation feedback to user #103 after registration of user #104")
            ->once();

        $this->account_creation_feedback->accountHasJustBeenCreated($new_user);
    }
}
