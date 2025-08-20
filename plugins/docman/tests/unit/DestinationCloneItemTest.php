<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman;

use Docman_CloneItemsVisitor;
use Docman_Folder;
use Docman_ItemFactory;
use Docman_LinkVersionFactory;
use LogicException;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DestinationCloneItemTest extends TestCase
{
    public function testDestinationForACloneCanBeBuiltFromAFolder(): void
    {
        $folder               = new Docman_Folder(['item_id' => 12, 'group_id' => 102]);
        $project_manager      = $this->createMock(ProjectManager::class);
        $link_version_factory = $this->createMock(Docman_LinkVersionFactory::class);

        $dispatcher = EventDispatcherStub::withIdentityCallback();

        $destination_clone_item = DestinationCloneItem::fromNewParentFolder(
            $folder,
            $project_manager,
            $link_version_factory,
            $dispatcher,
        );
        self::assertEquals(12, $destination_clone_item->getNewParentID());
        self::assertEquals(
            new Docman_CloneItemsVisitor(102, $project_manager, $link_version_factory, $dispatcher),
            $destination_clone_item->getCloneItemsVisitor()
        );
    }

    public function testDestinationForACloneCanBeUsedToCreateRootFolder(): void
    {
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $item_factory = $this->createMock(Docman_ItemFactory::class);
        $item_factory->method('getRoot')->willReturn(null);
        $project_manager      = $this->createMock(ProjectManager::class);
        $link_version_factory = $this->createMock(Docman_LinkVersionFactory::class);

        $dispatcher = EventDispatcherStub::withIdentityCallback();

        $destination_clone_item = DestinationCloneItem::fromDestinationProject(
            $item_factory,
            $project,
            $project_manager,
            $link_version_factory,
            $dispatcher,
        );
        self::assertEquals(0, $destination_clone_item->getNewParentID());
        self::assertEquals(
            new Docman_CloneItemsVisitor(102, $project_manager, $link_version_factory, $dispatcher),
            $destination_clone_item->getCloneItemsVisitor()
        );
    }

    public function testDestinationForACloneToBuildTheRootFolderCanConstructedWhenARootFolderAlreadyExist(): void
    {
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $item_factory = $this->createMock(Docman_ItemFactory::class);
        $item_factory->method('getRoot')->willReturn(new Docman_Folder());

        $this->expectException(LogicException::class);
        DestinationCloneItem::fromDestinationProject(
            $item_factory,
            $project,
            $this->createMock(ProjectManager::class),
            $this->createMock(Docman_LinkVersionFactory::class),
            EventDispatcherStub::withIdentityCallback(),
        );
    }
}
