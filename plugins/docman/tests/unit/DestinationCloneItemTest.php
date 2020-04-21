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
use Mockery;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;

final class DestinationCloneItemTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDestinationForACloneCanBeBuiltFromAFolder(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getGroupId')->andReturn('102');
        $folder->shouldReceive('getId')->andReturn('12');
        $project_manager        = Mockery::mock(ProjectManager::class);
        $link_version_factory   = Mockery::mock(Docman_LinkVersionFactory::class);
        $destination_clone_item = DestinationCloneItem::fromNewParentFolder(
            $folder,
            $project_manager,
            $link_version_factory
        );
        $this->assertEquals(12, $destination_clone_item->getNewParentID());
        $this->assertEquals(
            new Docman_CloneItemsVisitor(102, $project_manager, $link_version_factory),
            $destination_clone_item->getCloneItemsVisitor()
        );
    }

    public function testDestinationForACloneCanBeUsedToCreateRootFolder(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('getRoot')->andReturn(null);
        $project_manager      = Mockery::mock(ProjectManager::class);
        $link_version_factory = Mockery::mock(Docman_LinkVersionFactory::class);

        $destination_clone_item = DestinationCloneItem::fromDestinationProject(
            $item_factory,
            $project,
            $project_manager,
            $link_version_factory
        );
        $this->assertEquals(0, $destination_clone_item->getNewParentID());
        $this->assertEquals(
            new Docman_CloneItemsVisitor(102, $project_manager, $link_version_factory),
            $destination_clone_item->getCloneItemsVisitor()
        );
    }

    public function testDestinationForACloneToBuildTheRootFolderCanConstructedWhenARootFolderAlreadyExist(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn('102');
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('getRoot')->andReturn(Mockery::mock(Docman_Folder::class));

        $this->expectException(LogicException::class);
        DestinationCloneItem::fromDestinationProject(
            $item_factory,
            $project,
            Mockery::mock(ProjectManager::class),
            Mockery::mock(Docman_LinkVersionFactory::class)
        );
    }
}
