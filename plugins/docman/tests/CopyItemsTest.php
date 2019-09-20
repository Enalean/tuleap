<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

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
class CopyItemsTest extends TuleapTestCase
{

    function testDocumentCopyWithinTheSameProject()
    {
        $srcGroupId = $dstGroupId = 1789;

        $item_to_clone = \Mockery::spy(Docman_Link::class);
        $item_to_clone->shouldReceive('getId')->andReturns(25);
        $item_to_clone->shouldReceive('getGroupId')->andReturns($srcGroupId);
        $item_to_clone->shouldReceive('getMetadataIterator')->andReturns(\Mockery::spy('Iterator'));

        $new_id = 52;

        $dest_folder = \Mockery::spy(Docman_Folder::class);
        $dest_folder->shouldReceive('getId')->andReturns(33);

        $cloneItemsVisitor = \Mockery::mock(
            Docman_CloneItemsVisitor::class,
            [$dstGroupId, Mockery::mock(ProjectManager::class), Mockery::mock(Docman_LinkVersionFactory::class)]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        // expectations
        // - create new item
        $itemFactory = \Mockery::spy(Docman_ItemFactory::class);
        $itemFactory->shouldReceive('rawCreate')->andReturns($new_id);
        $cloneItemsVisitor->shouldReceive('_getItemFactory')->andReturns($itemFactory);
        // - apply perms
        $dPm = \Mockery::spy(Docman_PermissionsManager::class);
        $dPm->shouldReceive('setDefaultItemPermissions')->never();
        $dPm->shouldReceive('cloneItemPermissions')->with($item_to_clone->getId(), $new_id, $dstGroupId)->once();
        $cloneItemsVisitor->shouldReceive('_getPermissionsManager')->andReturns($dPm);

        $newMdvFactory = \Mockery::spy(Docman_MetadataValueFactory::class);

        $cloneItemsVisitor->shouldReceive('_getMetadataValueFactory')->once()->andReturns($newMdvFactory);

        $oldMdFactory = \Mockery::spy(Docman_MetadataFactory::class);
        $oldMdFactory->shouldReceive('appendItemMetadataList')->once();
        $cloneItemsVisitor->shouldReceive('_getMetadataFactory')->andReturns($oldMdFactory);

        $srcSettingsBo = \Mockery::spy(Docman_SettingsBo::class);
        $srcSettingsBo->shouldReceive('getMetadataUsage')->andReturns(true);
        $cloneItemsVisitor->shouldReceive('_getSettingsBo')->with($srcGroupId)->andReturns($srcSettingsBo);

        $dstSettingsBo = \Mockery::spy(Docman_SettingsBo::class);
        $dstSettingsBo->shouldReceive('getMetadataUsage')->andReturns(true);
        $cloneItemsVisitor->shouldReceive('_getSettingsBo')->with($dstGroupId)->andReturns($dstSettingsBo);

        $cloneItemsVisitor->visitLink($item_to_clone, array(
            'parentId'        => $dest_folder->getId(),
            'srcRootId'       => 66,
            'user'            => mock('PFUser'),
            'metadataMapping' => array(),
            'ugroupsMapping'  => array(),
            'data_root'       => '/tmp'));
    }
}
