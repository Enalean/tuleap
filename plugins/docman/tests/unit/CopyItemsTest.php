<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use Docman_CloneItemsVisitor;
use Docman_Folder;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersionFactory;
use Docman_MetadataFactory;
use Docman_MetadataValueFactory;
use Docman_PermissionsManager;
use Docman_SettingsBo;
use ProjectManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

/**
 * Test how items are copied.
 *
 * Cases:
 * - In the same project, everything is cloned (datas, permissions, metadata).
 * - Across projects
 *   - data (wiki page name, files, links) are copied.
 *   - permissions of new parent are recursively applied.
 *   - hard coded metadata are copied if enabled in destination project.
 *   - real metadatas are copied when
 *     - both metadata exist in the source and in the destination project
 *       - same name
 *       - same type
 *     - for list of values, the same value exist in both projects.
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CopyItemsTest extends TestCase
{
    public function testDocumentCopyWithinTheSameProject(): void
    {
        $srcGroupId = $dstGroupId = 1789;

        $item_to_clone = new Docman_Link(['item_id' => 25, 'group_id' => $srcGroupId, 'link_url' => '']);

        $new_id = 52;

        $dest_folder = new Docman_Folder(['item_id' => 33]);

        $cloneItemsVisitor = $this->getMockBuilder(Docman_CloneItemsVisitor::class)
            ->setConstructorArgs([
                $dstGroupId,
                $this->createMock(ProjectManager::class),
                $this->createMock(Docman_LinkVersionFactory::class),
                EventDispatcherStub::withIdentityCallback(),
            ])
            ->onlyMethods([
                '_getItemFactory',
                '_getPermissionsManager',
                '_getMetadataValueFactory',
                '_getMetadataFactory',
                '_getSettingsBo',
            ])
            ->getMock();

        // expectations
        // - create new item
        $itemFactory = $this->createMock(Docman_ItemFactory::class);
        $itemFactory->method('rawCreate')->willReturn($new_id);
        $itemFactory->method('getItemFromDb');
        $cloneItemsVisitor->method('_getItemFactory')->willReturn($itemFactory);
        // - apply perms
        $dPm = $this->createMock(Docman_PermissionsManager::class);
        $dPm->expects(self::never())->method('setDefaultItemPermissions');
        $dPm->expects(self::once())->method('cloneItemPermissions')->with($item_to_clone->getId(), $new_id, $dstGroupId);
        $cloneItemsVisitor->method('_getPermissionsManager')->willReturn($dPm);

        $newMdvFactory = $this->createMock(Docman_MetadataValueFactory::class);
        $cloneItemsVisitor->expects(self::once())->method('_getMetadataValueFactory')->willReturn($newMdvFactory);

        $oldMdFactory = $this->createMock(Docman_MetadataFactory::class);
        $oldMdFactory->expects(self::once())->method('appendItemMetadataList');
        $cloneItemsVisitor->method('_getMetadataFactory')->willReturn($oldMdFactory);

        $settingsBo = $this->createMock(Docman_SettingsBo::class);
        $settingsBo->method('getMetadataUsage')->willReturn(true);
        $cloneItemsVisitor->method('_getSettingsBo')->willReturn($settingsBo);

        $cloneItemsVisitor->visitLink($item_to_clone, [
            'parentId'        => $dest_folder->getId(),
            'srcRootId'       => 66,
            'user'            => UserTestBuilder::buildWithDefaults(),
            'metadataMapping' => [],
            'ugroupsMapping'  => [],
            'data_root'       => '/tmp',
        ]);
    }
}
