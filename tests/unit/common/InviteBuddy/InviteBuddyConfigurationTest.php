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
use Tuleap\ForgeConfigSandbox;

class InviteBuddyConfigurationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    public function testByDefaultBuddiesCannotBeInvitedCanBuddiesBeInvited(): void
    {
        $user = \Mockery::mock(\PFUser::class);

        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfTheFeatureIsDisabled(): void
    {
        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_BUDDIES_CAN_INVITED, 0);
        $user = \Mockery::mock(\PFUser::class);

        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfTheFeatureIsEnabledButUserIsAnonymous(): void
    {
        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_BUDDIES_CAN_INVITED, 1);
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isLoggedIn' => false])->getMock();

        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));
    }

    public function testBuddiesCanBeInvitedIfTheFeatureIsEnabledAndUserIsLoggedIn(): void
    {
        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_BUDDIES_CAN_INVITED, 1);
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isLoggedIn' => true])->getMock();

        self::assertTrue((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));
    }

    public function testBuddiesCannotBeInvitedIfTheFeatureIsEnabledButNbMaxIsLesserOrEqualThanZero(): void
    {
        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_BUDDIES_CAN_INVITED, 1);
        $user = \Mockery::mock(\PFUser::class)->shouldReceive(['isLoggedIn' => true])->getMock();

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 0);
        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, "invalid value");
        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, -1);
        self::assertFalse((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));

        \ForgeConfig::set(InviteBuddyConfiguration::CONFIG_MAX_INVITATIONS_BY_DAY, 1);
        self::assertTrue((new InviteBuddyConfiguration())->canBuddiesBeInvited($user));
    }
}
