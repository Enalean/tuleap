<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DocmanFileLastVersionProviderTest extends TestCase
{
    private const PROJECT_ID = 102;

    /**
     * @var \Docman_ItemFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $item_factory;
    /**
     * @var \Docman_VersionFactory&\PHPUnit\Framework\MockObject\Stub
     */
    private $version_factory;
    /**
     * @var \Docman_PermissionsManager&\PHPUnit\Framework\MockObject\Stub
     */
    private $permissions_manager;
    private DocmanFileLastVersionProvider $provider;

    protected function setUp(): void
    {
        $this->item_factory    = $this->createStub(\Docman_ItemFactory::class);
        $this->version_factory = $this->createStub(\Docman_VersionFactory::class);

        $this->provider = new DocmanFileLastVersionProvider(
            $this->item_factory,
            $this->version_factory,
        );

        $this->permissions_manager = $this->createStub(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);
    }

    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testCanRetrieveTheLastVersionOfADocmanFile(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_File(['group_id' => self::PROJECT_ID]));
        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $expected_version = new \Docman_Version();
        $this->version_factory->method('getCurrentVersionForItem')->willReturn($expected_version);

        $result = $this->provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 741);

        self::assertTrue(Result::isOk($result));
        self::assertSame($expected_version, $result->unwrapOr(null));
    }

    public function testCannotRetrieveANonExistingFile(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(null);

        $result = $this->provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 404);

        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveTheVersionOfAnItemThatIsNotAFile(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_Folder());

        $result = $this->provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 999);

        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveTheVersionOfAnItemTheUserCannotAccess(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_File(['group_id' => self::PROJECT_ID]));
        $this->permissions_manager->method('userCanAccess')->willReturn(false);

        $result = $this->provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 403);


        self::assertTrue(Result::isErr($result));
    }

    public function testCannotRetrieveANonExistantVersion(): void
    {
        $this->item_factory->method('getItemFromDb')->willReturn(new \Docman_File(['group_id' => self::PROJECT_ID]));
        $this->permissions_manager->method('userCanAccess')->willReturn(true);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn(null);

        $result = $this->provider->getLastVersionOfAFileUserCanAccess(UserTestBuilder::buildWithDefaults(), 852);

        self::assertTrue(Result::isErr($result));
    }
}
