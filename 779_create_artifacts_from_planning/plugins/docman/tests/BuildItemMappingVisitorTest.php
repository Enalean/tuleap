<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once(dirname(__FILE__).'/../include/Docman_BuildItemMappingVisitor.class.php');

Mock::generate('DataAccessResult');
Mock::generate('Docman_ItemDao');
Mock::generate('Docman_PermissionsManager');
Mock::generate('User');
Mock::generatePartial('Docman_BuildItemMappingVisitor', 'BuildItemMappingVisitorTestVersion', array('getItemDao', 'getPermissionsManager', 'getCurrentUser'));

class BuildItemMappingVisitorTest extends UnitTestCase {

    function setUp() {
    }

    function tearDown() {
    }

    function testCompareFolderChildrenOk() {
        // Src (reference)
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1', 'rank' => -2));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 1', 'rank' => -1));
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 =& new Docman_Folder(array('item_id' => 36, 'title' => 'Folder 1', 'rank' => -4));
        $fld40 =& new Docman_Folder(array('item_id' => 40, 'title' => 'Folder 1', 'rank' => -2));
        $node =& new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor =& new Docman_BuildItemMappingVisitor(569);
        $nodesOk = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        $this->assertEqual($nodesOk, array(150 => true,
                                           135 => true));
        $itemMapping = $itemMappingVisitor->getItemMapping();
        $this->assertEqual($itemMapping, array(150 => 36,
                                               135 => 40));
    }

    /**
     * Same test as above (testCompareFolderChildrenOk) but ranks inversion between item 36 & 40  (here 40 appears befor 36).
     */
    function testCompareFolderChildrenRankIssue() {
        // Src (reference)
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1', 'rank' => -2));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 1', 'rank' => -1));
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 =& new Docman_Folder(array('item_id' => 36, 'title' => 'Folder 1', 'rank' => -1));
        $fld40 =& new Docman_Folder(array('item_id' => 40, 'title' => 'Folder 1', 'rank' => -8));
        $node =& new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor =& new Docman_BuildItemMappingVisitor(569);
        $itemMappingVisitor->compareFolderChildren($fld140, $node);
        $itemMapping = $itemMappingVisitor->getItemMapping();
        $this->assertEqual($itemMapping, array(150 => 40,
                                               135 => 36));
    }

    /**
     * Test when there are more items in the source tree (reference) than the destination one.
     */
     function testCompareFolderChildrenMoreSrcThanDst() {
        // Src (reference)
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1', 'rank' => -2));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 1', 'rank' => -1));
        $fld136 =& new Docman_Folder(array('item_id' => 136, 'title' => 'Folder 1', 'rank' => 0));
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld140->addItem($fld136);

        // Dst
        $fld36 =& new Docman_Folder(array('item_id' => 36, 'title' => 'Folder 1', 'rank' => -4));
        $fld40 =& new Docman_Folder(array('item_id' => 40, 'title' => 'Folder 1', 'rank' => -2));
        $node =& new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor =& new Docman_BuildItemMappingVisitor(569);
        $nodesOk = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        $this->assertEqual($nodesOk, array(150 => true,
                                           135 => true));
        $itemMapping = $itemMappingVisitor->getItemMapping();
        $this->assertEqual($itemMapping, array(150 => 36,
                                               135 => 40));
     }

    /**
     * Test when there are more items in the destination tree than the source one.
     */
     function testCompareFolderChildrenMoreDstThanSrc() {
        // Src (reference)
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1', 'rank' => -2));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 1', 'rank' => -1));
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 =& new Docman_Folder(array('item_id' => 36, 'title' => 'Folder 1', 'rank' => -4));
        $fld40 =& new Docman_Folder(array('item_id' => 40, 'title' => 'Folder 1', 'rank' => -2));
        $fld72 =& new Docman_Folder(array('item_id' => 72, 'title' => 'Folder 1', 'rank' => 5));
        $node =& new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);
        $node->addItem($fld72);

        $itemMappingVisitor =& new Docman_BuildItemMappingVisitor(569);
        $nodesOk = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        $this->assertEqual($nodesOk, array(150 => true,
                                           135 => true));
        $itemMapping = $itemMappingVisitor->getItemMapping();
        $this->assertEqual($itemMapping, array(150 => 36,
                                               135 => 40));
       }

    /**
     * Test: We want to find the item id mapping for the tree on the left. We
     *      look for matching values in the tree on the right.
     *      ______________________________
     *     _|________                    _|_________
     * 140 Project doc                35 Project doc
     * |-- 150 Folder 1               |-- 36 Folder 1
     * |   |-- 112 Folder 1.1         |   |-- 37 Folder 1.1
     * |   |   `-- 113 Folder 1.1.1   |   |   `-- 38 Folder 1.1.1
     * |   |       `-- *              |   |       `-- *
     * |   `-- 115 Folder 1.2         |   `-- 39 Toto
     * |       `-- *                  |       `-- *
     * `-- 135 Folder 2               `-- 40 Folder 2
     *     `-- *                          `-- *
     *
     * Here is the tree build by Docman_ItemFactory::findPathToRoot(113,115,135);
     * Project documentation (140)
     * |-- Folder 1 (150)
     * |   |-- Folder 1.1 (112)
     * |   |    `-- Folder 1.1.1 (113)
     * |   `-- Folder 1.2 (115)
     * `-- Folder 2 (135)
     */
    function testSimpleTree() {
        // Nodes definition
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation'));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1'));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'title' => 'Folder 1.1'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'title' => 'Folder 1.1.1'));
        $fld115 =& new Docman_Folder(array('item_id' => 115, 'title' => 'Folder 1.2'));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 2'));

        // Build tree
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld150->addItem($fld112);
        $fld150->addItem($fld115);
        $fld112->addItem($fld113);

        // Fake DB results
        $mockDao =& new MockDocman_ItemDao($this);

        // Init
        $mockDar0 =& new MockDataAccessResult($this);
        $mockDar0->setReturnValue('rowCount', 1);
        $mockDar0->setReturnValueAt(0, 'getRow', array('item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar0, array(array('Project documentation'), 569, 0));

        // Children of 35
        $mockDar35 =& new MockDataAccessResult($this);
        $mockDar35->setReturnValue('rowCount', 2);
        $mockDar35->setReturnValueAt(0, 'getRow', array('item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1));
        $mockDar35->setReturnValueAt(1, 'getRow', array('item_id' => 40, 'title' => 'Folder 2', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2));
        $mockDao->setReturnReference('searchByTitle', $mockDar35, array(array('Folder 1', 'Folder 2'), 569, 35));

        // Children of 36
        $mockDar36 =& new MockDataAccessResult($this);
        $mockDar36->setReturnValue('rowCount', 1);
        $mockDar36->setReturnValueAt(0, 'getRow', array('item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2));
        $mockDao->setReturnReference('searchByTitle', $mockDar36, array(array('Folder 1.1', 'Folder 1.2'), 569, 36));

        // Children of 37
        $mockDar37 =& new MockDataAccessResult($this);
        $mockDar37->setReturnValue('rowCount', 1);
        $mockDar37->setReturnValueAt(0, 'getRow', array('item_id' => 38, 'title' => 'Folder 1.1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar37, array(array('Folder 1.1.1'), 569, 37));

        //
        // Permissions mock
        $mockDPM  =& new MockDocman_PermissionsManager();
        $mockDPM->setReturnValue('userCanRead', true);
        $mockUser =& new MockUser();


        $itemMappingVisitor =& new BuildItemMappingVisitorTestVersion($this);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;
        $itemMappingVisitor->itemMapping = array();

        // Attach mocks
        $itemMappingVisitor->setReturnReference('getItemDao', $mockDao);
        $itemMappingVisitor->setReturnReference('getPermissionsManager', $mockDPM);
        $itemMappingVisitor->setReturnReference('getCurrentUser', $mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        $this->assertEqual($itemMapping, array(140 => 35,
                                               150 => 36,
                                               112 => 37,
                                               113 => 38,
                                               135 => 40));
    }

    /**
     * Same example, item 40 is not readable
     */
    function testSimpleTreePermissionDenied() {
        // Nodes definition
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation'));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1'));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'title' => 'Folder 1.1'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'title' => 'Folder 1.1.1'));
        $fld115 =& new Docman_Folder(array('item_id' => 115, 'title' => 'Folder 1.2'));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 2'));

        // Build tree
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld150->addItem($fld112);
        $fld150->addItem($fld115);
        $fld112->addItem($fld113);

        // Fake DB results
        $mockDao =& new MockDocman_ItemDao($this);

        // Init
        $mockDar0 =& new MockDataAccessResult($this);
        $mockDar0->setReturnValue('rowCount', 1);
        $mockDar0->setReturnValueAt(0, 'getRow', array('item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar0, array(array('Project documentation'), 569, 0));

        // Children of 35
        $mockDar35 =& new MockDataAccessResult($this);
        $mockDar35->setReturnValue('rowCount', 2);
        $mockDar35->setReturnValueAt(0, 'getRow', array('item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1));
        $mockDar35->setReturnValueAt(1, 'getRow', array('item_id' => 40, 'title' => 'Folder 2', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2));
        $mockDao->setReturnReference('searchByTitle', $mockDar35, array(array('Folder 1', 'Folder 2'), 569, 35));

        // Children of 36
        $mockDar36 =& new MockDataAccessResult($this);
        $mockDar36->setReturnValue('rowCount', 1);
        $mockDar36->setReturnValueAt(0, 'getRow', array('item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2));
        $mockDao->setReturnReference('searchByTitle', $mockDar36, array(array('Folder 1.1', 'Folder 1.2'), 569, 36));

        // Children of 37
        $mockDar37 =& new MockDataAccessResult($this);
        $mockDar37->setReturnValue('rowCount', 1);
        $mockDar37->setReturnValueAt(0, 'getRow', array('item_id' => 38, 'title' => 'Folder 1.1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar37, array(array('Folder 1.1.1'), 569, 37));

        //
        // Permissions mock
        $mockUser =& new MockUser();
        $mockDPM  =& new MockDocman_PermissionsManager();
        // Item 40 is unreadable
        $mockDPM->setReturnValue('userCanRead', false, array($mockUser, 40));
        // other items are readable
        $mockDPM->setReturnValue('userCanRead', true);

        $itemMappingVisitor =& new BuildItemMappingVisitorTestVersion($this);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;
        $itemMappingVisitor->itemMapping = array();

        // Attach mocks
        $itemMappingVisitor->setReturnReference('getItemDao', $mockDao);
        $itemMappingVisitor->setReturnReference('getPermissionsManager', $mockDPM);
        $itemMappingVisitor->setReturnReference('getCurrentUser', $mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        $this->assertEqual($itemMapping, array(140 => 35,
                                               150 => 36,
                                               112 => 37,
                                               113 => 38));
    }

    /**
     *
     * 140 Project doc                35 Project doc
     * |-- 150 Folder 1               |-- 36 Folder 1
     * |   |-- 112 Folder 1.1         |   |-- 37 Folder 1.1
     * |       `-- *                  |       `-- *
     * `-- 135 Folder 1               `-- 40 Folder 1
     *     `-- 173 Folder test             `-- 56 Folder test
     */
    function testSeveralFoldersWithSameName() {
        // Nodes definition
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'title' => 'Folder 1', 'rank' => -2));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'title' => 'Folder 1.1', 'rank' => 0));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'title' => 'Folder 1', 'rank' => -1));
        $fld173 =& new Docman_Folder(array('item_id' => 173, 'title' => 'Folder test', 'rank' => 0));

        // Build tree
        $fld140->addItem($fld135);
        $fld140->addItem($fld150);
        $fld150->addItem($fld112);
        $fld135->addItem($fld173);

        // Fake DB results
        $mockDao =& new MockDocman_ItemDao($this);

        // Init
        $mockDar0 =& new MockDataAccessResult($this);
        $mockDar0->setReturnValue('rowCount', 1);
        $mockDar0->setReturnValueAt(0, 'getRow', array('item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar0, array(array('Project documentation'), 569, 0));

        // Children of 35
        $mockDar35 =& new MockDataAccessResult($this);
        $mockDar35->setReturnValue('rowCount', 2);
        $mockDar35->setReturnValueAt(0, 'getRow', array('item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1));
        $mockDar35->setReturnValueAt(1, 'getRow', array('item_id' => 40, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2));
        $mockDao->setReturnReference('searchByTitle', $mockDar35, array(array('Folder 1', 'Folder 1'), 569, 35));

        // Children of 36
        $mockDar36 =& new MockDataAccessResult($this);
        $mockDar36->setReturnValue('rowCount', 1);
        $mockDar36->setReturnValueAt(0, 'getRow', array('item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2));
        $mockDao->setReturnReference('searchByTitle', $mockDar36, array(array('Folder 1.1'), 569, 36));

        // Children of 40
        $mockDar37 =& new MockDataAccessResult($this);
        $mockDar37->setReturnValue('rowCount', 1);
        $mockDar37->setReturnValueAt(0, 'getRow', array('item_id' => 56, 'title' => 'Folder test', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0));
        $mockDao->setReturnReference('searchByTitle', $mockDar37, array(array('Folder test'), 569, 40));

        //
        // Permissions mock
        $mockDPM  =& new MockDocman_PermissionsManager();
        $mockDPM->setReturnValue('userCanRead', true);
        $mockUser =& new MockUser();

        $itemMappingVisitor =& new BuildItemMappingVisitorTestVersion($this);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;
        $itemMappingVisitor->itemMapping = array();

        // Attach mocks
        $itemMappingVisitor->setReturnReference('getItemDao', $mockDao);
        $itemMappingVisitor->setReturnReference('getPermissionsManager', $mockDPM);
        $itemMappingVisitor->setReturnReference('getCurrentUser', $mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        $this->assertEqual($itemMapping, array(140 => 35,
                                               150 => 36,
                                               112 => 37,
                                               135 => 40,
                                               173 => 56));
    }

}

?>
