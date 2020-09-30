<?php
/*
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
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

final class InvitationLimitCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var InvitationLimitChecker
     */
    private $checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|InvitationDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao = \Mockery::mock(InvitationDao::class);
        $configuration = new InviteBuddyConfiguration(\Mockery::mock(\EventManager::class));

        $this->checker = new InvitationLimitChecker($this->dao, $configuration);

        ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 5);
    }

    public function testUserCanNotSendInvitationIfHeByPassLimit(): void
    {
        $nb_invitation_to_send = 3;
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(3);

        $this->expectException(InvitationSenderGateKeeperException::class);
        $this->checker->checkForNewInvitations($nb_invitation_to_send, $user);
    }

    public function testUserCanSendInvitationsIfHeDoesNotByPassLimit(): void
    {
        $nb_invitation_to_send = 3;
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->checker->checkForNewInvitations($nb_invitation_to_send, $user);

        $this->addToAssertionCount(1);
    }

    public function limitIsReachedWhenInvitationsAreSuperiorOrEqualToServerLimit(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(5);

        $this->assertTrue($this->checker->isLimitReached($user));
    }

    public function limitIsNotReachedOtherwise(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->dao->shouldReceive('getInvitationsSentByUserForToday')->andReturn(0);

        $this->assertFalse($this->checker->isLimitReached($user));
    }
}
