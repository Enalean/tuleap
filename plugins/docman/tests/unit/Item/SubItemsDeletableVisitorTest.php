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

namespace Tuleap\Docman\Item;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SubItemsDeletableVisitorTest extends TestCase
{
    /**
     * @return \Docman_Item[]
     */
    public static function getItems(): array
    {
        return [
            [new \Docman_Item()],
            [new \Docman_Document()],
            [new \Docman_File()],
            [new \Docman_EmbeddedFile()],
            [new \Docman_Wiki()],
            [new \Docman_Link()],
            [new \Docman_Empty()],
            [new \Docman_Folder()],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getItems')]
    public function testItReturnsTrueIfUserIsAllowedToDeleteTheItem(\Docman_Item $item): void
    {
        $user = UserTestBuilder::aUser()->build();

        $permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        $permissions_manager
            ->method('userCanDelete')
            ->with($user, $item)
            ->willReturn(true);

        self::assertTrue($item->accept(new SubItemsDeletableVisitor($permissions_manager, $user)));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getItems')]
    public function testItReturnsFalseIfUserIsNotAllowedToDeleteTheItem(\Docman_Item $item): void
    {
        $user = UserTestBuilder::aUser()->build();

        $permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        $permissions_manager
            ->method('userCanDelete')
            ->with($user, $item)
            ->willReturn(false);

        self::assertFalse($item->accept(new SubItemsDeletableVisitor($permissions_manager, $user)));
    }

    public function testItReturnsTrueIfUserIsAllowedToDeleteEachItemsInAFolderHierarchy(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $item   = new \Docman_Item();
        $folder = new \Docman_Folder();
        $folder->addItem($item);
        $parent_folder = new \Docman_Folder();
        $parent_folder->addItem($folder);

        $permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        $permissions_manager
            ->method('userCanDelete')
            ->willReturnMap([
                [$user, $parent_folder, true],
                [$user, $folder, true],
                [$user, $item, true],
            ]);

        self::assertTrue($item->accept(new SubItemsDeletableVisitor($permissions_manager, $user)));
    }

    public function testItReturnsFalseIfUserIsNotAllowedToDeleteAnItemDeepInAFolderHierarchy(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $item   = new \Docman_Item();
        $folder = new \Docman_Folder();
        $folder->addItem($item);
        $parent_folder = new \Docman_Folder();
        $parent_folder->addItem($folder);

        $permissions_manager = $this->createMock(\Docman_PermissionsManager::class);
        $permissions_manager
            ->method('userCanDelete')
            ->willReturnMap([
                [$user, $parent_folder, true],
                [$user, $folder, true],
                [$user, $item, false],
            ]);

        self::assertFalse($item->accept(new SubItemsDeletableVisitor($permissions_manager, $user)));
    }
}
