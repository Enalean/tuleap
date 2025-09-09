<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Artidoc\Domain\Document\UserCannotWriteDocumentFault;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CurrentUserHasArtidocPermissionsCheckerTest extends TestCase
{
    private const int PROJECT_ID = 101;
    private const int ITEM_ID    = 12;

    private \PFUser $user;
    private \Docman_PermissionsManager&MockObject $permissions_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        \Docman_PermissionsManager::setInstance(self::PROJECT_ID, $this->permissions_manager);
    }

    #[\Override]
    protected function tearDown(): void
    {
        \Docman_PermissionsManager::clearInstances();
    }

    public function testCheckUserCanReadReturnFault(): void
    {
        $checker = CurrentUserHasArtidocPermissionsChecker::withCurrentUser($this->user);

        $this->permissions_manager->method('userCanRead')->willReturn(false);

        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $result = $checker->checkUserCanRead($artidoc);
        self::assertTrue(Result::isErr($result));
    }

    public function testCheckUserCanReadReturnArtidocWhenUserIsAReader(): void
    {
        $checker = CurrentUserHasArtidocPermissionsChecker::withCurrentUser($this->user);

        $this->permissions_manager->method('userCanRead')->willReturn(true);

        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $result = $checker->checkUserCanRead($artidoc);
        self::assertTrue(Result::isOk($result));
        self::assertSame($artidoc, $result->value);
    }

    public function testCheckUserCanWriteReturnFault(): void
    {
        $checker = CurrentUserHasArtidocPermissionsChecker::withCurrentUser($this->user);

        $this->permissions_manager->method('userCanWrite')->willReturn(false);

        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $result = $checker->checkUserCanWrite($artidoc);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserCannotWriteDocumentFault::class, $result->error);
    }

    public function testCheckUserCanWriteReturnArtidocWhenUserIsAWriter(): void
    {
        $checker = CurrentUserHasArtidocPermissionsChecker::withCurrentUser($this->user);

        $this->permissions_manager->method('userCanWrite')->willReturn(true);

        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $result = $checker->checkUserCanWrite($artidoc);
        self::assertTrue(Result::isOk($result));
        self::assertSame($artidoc, $result->value);
    }
}
