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

namespace Tuleap\Docman\REST\v1\Files;

use Luracast\Restler\RestException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Tests\Stub\ICountVersionsStub;
use Tuleap\Docman\Tests\Stub\IDeleteVersionStub;
use Tuleap\Docman\Tests\Stub\IRetrieveVersionStub;
use Tuleap\Docman\Version\ICountVersions;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class FileVersionsDeletorTest extends TestCase
{
    private const int PROJECT_ID = 102;

    #[\Override]
    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testExceptionWhenVersionDoesNotExists(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withoutVersion(),
            IDeleteVersionStub::willSucceed(),
            $this->createMock(ICountVersions::class),
            $this->createMock(\Docman_ItemFactory::class),
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $deletor->delete(123, $user);
    }

    public function testExceptionWhenItemOfVersionDoesNotExists(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn(null);

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            IDeleteVersionStub::willSucceed(),
            $this->createMock(ICountVersions::class),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $deletor->delete(123, $user);
    }

    public function testExceptionWhenUserIsNotAllowedToDelete(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item    = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn($item);

        $permissions = $this->createMock(\Docman_PermissionsManager::class);
        $permissions
            ->method('userCanDelete')
            ->willReturn(false);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $permissions);

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            IDeleteVersionStub::willSucceed(),
            $this->createMock(ICountVersions::class),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $deletor->delete(123, $user);
    }

    public function testExceptionWhenItemIsNotExpectedType(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item    = new \Docman_EmbeddedFile(['group_id' => self::PROJECT_ID]);
        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn($item);

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            IDeleteVersionStub::willSucceed(),
            $this->createMock(ICountVersions::class),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $deletor->delete(123, $user);
    }

    public function testExceptionWhenDeletionFails(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item    = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn($item);

        $permissions = $this->createMock(\Docman_PermissionsManager::class);
        $permissions
            ->method('userCanDelete')
            ->willReturn(true);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $permissions);

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            IDeleteVersionStub::willFail(),
            ICountVersionsStub::buildTwoVersions(),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(UnableToDeleteVersionException::class);

        $deletor->delete(123, $user);
    }

    public function testExceptionWhenItemHasOnlyOneVersionLeft(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item    = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn($item);

        $permissions = $this->createMock(\Docman_PermissionsManager::class);
        $permissions
            ->method('userCanDelete')
            ->willReturn(true);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $permissions);

        $version_deletor = IDeleteVersionStub::willSucceed();

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            $version_deletor,
            ICountVersionsStub::buildOneVersion(),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $deletor->delete(123, $user);

        self::assertFalse($version_deletor->hasBeenCalled());
    }

    public function testHappyPath(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $item    = new \Docman_File(['group_id' => self::PROJECT_ID]);
        $version = new \Docman_Version();

        $item_factory = $this->createMock(\Docman_ItemFactory::class);
        $item_factory
            ->method('getItemFromDb')
            ->willReturn($item);

        $permissions = $this->createMock(\Docman_PermissionsManager::class);
        $permissions
            ->method('userCanDelete')
            ->willReturn(true);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $permissions);

        $version_deletor = IDeleteVersionStub::willSucceed();

        $deletor = new FileVersionsDeletor(
            new DoesItemHasExpectedTypeVisitor(\Docman_File::class),
            IRetrieveVersionStub::withVersion($version),
            $version_deletor,
            ICountVersionsStub::buildTwoVersions(),
            $item_factory,
            new DBTransactionExecutorPassthrough(),
        );

        $deletor->delete(123, $user);

        self::assertTrue($version_deletor->hasBeenCalled());
    }
}
