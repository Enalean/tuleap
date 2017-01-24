<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2006.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

require_once 'bootstrap.php';

Mock::generatePartial('Docman_CloneItemsVisitor',
                      'Docman_CloneItemsVisitorTest',
                      array('_getItemFactory',
                            '_getPermissionsManager',
                            '_getFileStorage',
                            '_getVersionFactory',
                            '_getMetadataValueFactory',
                            '_getMetadataFactory',
                            '_getSettingsBo',
                     ));

Mock::generate('Docman_SettingsBo');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_PermissionsManager');
Mock::generate('Docman_MetadataValueFactory');
Mock::generate('Docman_MetadataFactory');
Mock::generate('Docman_Link');
Mock::generate('Docman_Folder');
Mock::generate('Iterator');

Mock::generate('PFUser');

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
class CopyItemsTest extends TuleapTestCase {

    function testDocumentCopyWithinTheSameProject() {
        $srcGroupId = $dstGroupId = 1789;

        $item_to_clone = new MockDocman_Link();
        $item_to_clone->setReturnValue('getId', 25);
        $item_to_clone->setReturnValue('getGroupId', $srcGroupId);
        $item_to_clone->setReturnReference('getMetadataIterator', new MockIterator());

        $new_id = 52;

        $dest_folder = new MockDocman_Folder();
        $dest_folder->setReturnValue('getId', 33);

        $cloneItemsVisitor = new Docman_CloneItemsVisitorTest($this);

        // expectations
        // - create new item
        $itemFactory = new MockDocman_ItemFactory($this);
        $itemFactory->setReturnValue('rawCreate', $new_id);
        $cloneItemsVisitor->setReturnReference('_getItemFactory', $itemFactory);
        // - apply perms
        $dPm = new MockDocman_PermissionsManager($this);
        $dPm->expectNever('setDefaultItemPermissions');
        $dPm->expectOnce('cloneItemPermissions', array($item_to_clone->getId(), $new_id, $dstGroupId));
        $cloneItemsVisitor->setReturnReference('_getPermissionsManager', $dPm);

        $newMdvFactory = new MockDocman_MetadataValueFactory($this);
        $oldMdvFactory = new MockDocman_MetadataValueFactory($this);

        $cloneItemsVisitor->setReturnReferenceAt(0, '_getMetadataValueFactory', $newMdvFactory);
        $cloneItemsVisitor->setReturnReferenceAt(1, '_getMetadataValueFactory', $oldMdvFactory);

        $oldMdFactory = new MockDocman_MetadataFactory($this);
        $oldMdFactory->expectOnce('appendItemMetadataList');
        $cloneItemsVisitor->setReturnReference('_getMetadataFactory', $oldMdFactory);

        $srcSettingsBo = new MockDocman_SettingsBo($this);
        $srcSettingsBo->setReturnValue('getMetadataUsage', true);
        $cloneItemsVisitor->setReturnReference('_getSettingsBo', $srcSettingsBo, array($srcGroupId));

        $dstSettingsBo = new MockDocman_SettingsBo($this);
        $dstSettingsBo->setReturnValue('getMetadataUsage', true);
        $cloneItemsVisitor->setReturnReference('_getSettingsBo', $dstSettingsBo, array($dstGroupId));

        $cloneItemsVisitor->Docman_CloneItemsVisitor($dstGroupId);
        $cloneItemsVisitor->visitLink($item_to_clone, array(
            'parentId'        => $dest_folder->getId(),
            'srcRootId'       => 66,
            'user'            => mock('PFUser'),
            'metadataMapping' => array(),
            'ugroupsMapping'  => array(),
            'data_root'       => '/tmp')
        );
    }
}
