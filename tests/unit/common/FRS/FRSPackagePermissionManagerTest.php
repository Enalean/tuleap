<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use FRSPackage;
use FRSPackageFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FRSPackagePermissionManagerTest extends TestCase
{
    /**
     * @var MockObject&FRSPackageFactory
     */
    private $package_factory;


    /**
     * @var PackagePermissionManager
     */
    private $package_permission_manager;

    /**
     * @var PFUser
     */
    private $user;

    #[\Override]
    public function setUp(): void
    {
        $this->package_factory = $this->createMock(FRSPackageFactory::class);

        $this->package_permission_manager = new PackagePermissionManager(
            $this->package_factory
        );

        $this->user = UserTestBuilder::buildWithDefaults();
    }

    public function testItReturnsTrueWhenUserCanReadThePackage(): void
    {
        $package = new FRSPackage(['package_id' => 101]);
        $this->package_factory->method('userCanRead')->with(101, $this->user->getId())->willReturn(true);

        self::assertTrue(
            $this->package_permission_manager->canUserSeePackage($this->user, $package)
        );
    }

    public function testItReturnsFalseWhenUserCannotReadThePackage(): void
    {
        $package = new FRSPackage(['package_id' => 101]);
        $this->package_factory->method('userCanRead')->with(101, $this->user->getId())->willReturn(false);

        self::assertFalse(
            $this->package_permission_manager->canUserSeePackage($this->user, $package)
        );
    }
}
