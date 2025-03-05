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

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\User\Account\RegistrationGuardEvent;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InviteBuddyConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testBuddiesCannotBeInvitedIfUserIsAnonymous(): void
    {
        $user          = UserTestBuilder::anAnonymousUser()->build();
        $event_manager = EventDispatcherStub::withIdentityCallback();

        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCanBeInvitedIfTheUserIsLoggedIn(): void
    {
        $user          = UserTestBuilder::anActiveUser()->build();
        $event_manager = EventDispatcherStub::withIdentityCallback();

        self::assertTrue((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfThePlatformPreventsUsersToRegister(): void
    {
        $user          = UserTestBuilder::anActiveUser()->build();
        $event_manager = EventDispatcherStub::withCallback(function (RegistrationGuardEvent $event): RegistrationGuardEvent {
            $event->disableRegistration();
            return $event;
        });

        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfNbMaxIsLesserOrEqualThanOne(): void
    {
        $user          = UserTestBuilder::anActiveUser()->build();
        $event_manager = EventDispatcherStub::withIdentityCallback();

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 0);
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 'invalid value');
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, -1);
        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 1);
        self::assertTrue((new InviteBuddyConfiguration($event_manager))->canBuddiesBeInvited($user));
    }

    public function testSiteAdminCanConfigureWhenNoPluginPreventIt(): void
    {
        $event_manager = EventDispatcherStub::withIdentityCallback();
        self::assertTrue((new InviteBuddyConfiguration($event_manager))->canSiteAdminConfigureTheFeature());
    }

    public function testSiteAdminCannotConfigureWhenPluginPreventIt(): void
    {
        $event_manager = EventDispatcherStub::withCallback(function (RegistrationGuardEvent $event): RegistrationGuardEvent {
            $event->disableRegistration();
            return $event;
        });

        self::assertFalse((new InviteBuddyConfiguration($event_manager))->canSiteAdminConfigureTheFeature());
    }

    public function itReturnsTheNbMax(): void
    {
        $event_manager = EventDispatcherStub::withIdentityCallback();

        self::assertEquals(
            InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY,
            (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay()
        );

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 'invalid value');
        self::assertEquals(0, (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay());

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 13);
        self::assertEquals(13, (new InviteBuddyConfiguration($event_manager))->getNbMaxInvitationsByDay());
    }
}
