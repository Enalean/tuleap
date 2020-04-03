<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\ForgeAccess;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;

class ForgeAccessTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private $permissions_overrider_manager;

    protected function setUp(): void
    {
        $this->permissions_overrider_manager = \Mockery::mock(\PermissionsOverrider_PermissionsOverriderManager::class);
        \PermissionsOverrider_PermissionsOverriderManager::setInstance($this->permissions_overrider_manager);
    }

    protected function tearDown(): void
    {
        \PermissionsOverrider_PermissionsOverriderManager::clearInstance();
    }

    public function testLoginIsNotRequiredWhenAnonymousAreAllowedAndThereIsNoPermissionOverride(): void
    {
        $forge_access = new \ForgeAccess($this->permissions_overrider_manager);

        \ForgeConfig::set(\ForgeAccess::CONFIG, \ForgeAccess::ANONYMOUS);
        $this->permissions_overrider_manager->shouldReceive('doesOverriderForceUsageOfAnonymous')->andReturns(false);

        $this->assertFalse($forge_access->doesPlatformRequireLogin());
    }

    public function testLoginIsRequiredWhenAnonymousAreNotAllowedAndThereIsNoPermissionOverride(): void
    {
        $forge_access = new \ForgeAccess($this->permissions_overrider_manager);

        \ForgeConfig::set(\ForgeAccess::CONFIG, \ForgeAccess::REGULAR);
        $this->permissions_overrider_manager->shouldReceive('doesOverriderForceUsageOfAnonymous')->andReturns(false);
        $this->permissions_overrider_manager->shouldReceive('doesOverriderAllowUserToAccessPlatform')->andReturns(false);

        $this->assertTrue($forge_access->doesPlatformRequireLogin());
    }

    public function testLoginRequirementCanBeOverriddenByThePermissionOverrider(): void
    {
        $forge_access = new \ForgeAccess($this->permissions_overrider_manager);

        \ForgeConfig::set(\ForgeAccess::CONFIG, \ForgeAccess::REGULAR);
        $this->permissions_overrider_manager->shouldReceive('doesOverriderForceUsageOfAnonymous')->andReturns(false);
        $this->permissions_overrider_manager->shouldReceive('doesOverriderAllowUserToAccessPlatform')->andReturns(true);

        $this->assertFalse($forge_access->doesPlatformRequireLogin());
    }
}
