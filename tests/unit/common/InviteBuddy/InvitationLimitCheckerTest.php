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
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InvitationLimitCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private InvitationLimitChecker $checker;
    private InvitationDao&\PHPUnit\Framework\MockObject\MockObject $dao;

    protected function setUp(): void
    {
        $this->dao     = $this->createMock(InvitationDao::class);
        $configuration = new InviteBuddyConfiguration(EventDispatcherStub::withIdentityCallback());

        $this->checker = new InvitationLimitChecker($this->dao, $configuration);

        ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 5);
    }

    public function testUserCanNotSendInvitationIfHeByPassLimit(): void
    {
        $nb_invitation_to_send = 3;
        $user                  = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(3);

        $this->expectException(InvitationSenderGateKeeperException::class);
        $this->checker->checkForNewInvitations($nb_invitation_to_send, $user);
    }

    public function testUserCanNotSendInvitationIfHeAlreadyExceedsLimitForTheDay(): void
    {
        $nb_invitation_to_send = 2;
        $user                  = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(6);

        $this->expectException(InvitationSenderGateKeeperException::class);
        $this->expectExceptionMessage('You are trying to send 2 invitations, but the maximum is 5 per day.');

        $this->checker->checkForNewInvitations($nb_invitation_to_send, $user);
    }

    public function testUserCanSendInvitationsIfHeDoesNotByPassLimit(): void
    {
        $nb_invitation_to_send = 3;
        $user                  = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->checker->checkForNewInvitations($nb_invitation_to_send, $user);

        $this->addToAssertionCount(1);
    }

    public function testLimitIsReachedWhenInvitationsAreSuperiorOrEqualToServerLimit(): void
    {
        $user = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(5);

        $this->assertTrue($this->checker->isLimitReached($user));
    }

    public function testLimitIsNotReachedOtherwise(): void
    {
        $user = UserTestBuilder::aUser()->withId(101)->build();

        $this->dao->method('getInvitationsSentByUserForToday')->willReturn(0);

        $this->assertFalse($this->checker->isLimitReached($user));
    }
}
