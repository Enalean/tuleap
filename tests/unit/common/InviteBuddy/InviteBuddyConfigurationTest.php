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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\User\Account\RegistrationGuardEvent;

class InviteBuddyConfigurationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testBuddiesCannotBeInvitedIfUserIsAnonymous(): void
    {
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isAnonymous' => true])->getMock();
        $event_manager = \Mockery::mock(EventDispatcherInterface::class);
        $event_manager
            ->shouldReceive('dispatch')
            ->andReturn(new RegistrationGuardEvent());

        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCanBeInvitedIfTheUserIsLoggedIn(): void
    {
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isAnonymous' => false])->getMock();
        $event_manager = \Mockery::mock(EventDispatcherInterface::class);
        $event_manager
            ->shouldReceive('dispatch')
            ->andReturn(new RegistrationGuardEvent());

        self::assertTrue((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfThePlatformPreventsUsersToRegister(): void
    {
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isAnonymous' => false])->getMock();
        $event_manager = \Mockery::mock(EventDispatcherInterface::class);
        $guard_event = new RegistrationGuardEvent();
        $guard_event->disableRegistration();
        $event_manager
            ->shouldReceive('dispatch')
            ->andReturn($guard_event);

        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfNbMaxIsLesserOrEqualThanOne(): void
    {
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isAnonymous' => false])->getMock();
        $event_manager = \Mockery::mock(EventDispatcherInterface::class);
        $event_manager
            ->shouldReceive('dispatch')
            ->andReturn(new RegistrationGuardEvent());

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 0);
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, "invalid value");
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, -1);
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 1);
        self::assertTrue((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function itReturnsTheNbMax(): void
    {
        $event_manager = \Mockery::mock(EventDispatcherInterface::class);

        self::assertEquals(
            InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY,
            (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay()
        );

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, "invalid value");
        self::assertEquals(0, (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay());

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 13);
        self::assertEquals(13, (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay());
    }
}
