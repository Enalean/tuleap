<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Sabri LABBENE, 2007.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__).'/../include/Docman_ItemFactory.class.php');

Mock::generate('DataAccessResult');
Mock::generate('Docman_ItemDao');
Mock::generatePartial('Docman_ItemFactory', 'Docman_ItemFactoryTestVersion', array('_getItemDao'));

class Docman_ItemFactoryTest extends UnitTestCase {

    function Docman_ItemFactoryTest ($name = 'Docman_ItemFactory test') {
	    $this->UnitTestCase($name);	
	}

    /**
     * 140
     * `-- 150
     *     `-- 112
     *         `-- 113
     *             `-- *
     *
     * Find path to root for 113
     */
    function testconnectOrphansToParentsStep1() {
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        $itemFactory =& new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113);
        $orphans = array(113 => 113);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(112));
        $this->assertEqual($orphans, array(113 => 113));
        $this->assertEqual($itemList, array(113 => $fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep2() {
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;

        $c_fld112->addItem($c_fld113);

        $itemFactory =& new Docman_ItemFactory(0);

        $itemList = array(112 => $fld112, 113 => $fld113);
        $orphans  = array(112 => 112, 113 => 113);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(150));
        $this->assertEqual($orphans, array(112 => 112));
        $this->assertEqual($itemList,    array(112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep3() {
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150'));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld112);

        $itemFactory =& new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = array(150 => $fld150, 112 => $fld112, 113 => $fld113);
        $orphans  = array(150 => 150, 112 => 112);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(140));
        $this->assertEqual($orphans, array(150 => 150));
        $this->assertEqual($itemList, array(150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep4() {
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation'));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150'));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld140 = $fld140;
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld112);
        $c_fld140->addItem($c_fld150);

        $itemFactory =& new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $fld150->addItem($fld112);
        $itemList = array(140 => $fld140, 150 => $fld150, 112 => $fld112, 113 => $fld113);
        $orphans  = array(140 => 140, 150 => 150);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array());
        $this->assertEqual($itemList, array(140 => $c_fld140, 150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertEqual($rootId, 140);
    }

    /**
     * 140
     * `-- 150 (unreadable)
     *     `-- 112
     *         `-- 113
     *             `-- *
     *
     * Find path to root for 113.
     * Correspond to testconnectOrphansToParentsStep3.
     * but item 150 is to readable by user.
     */
    function testconnectOrphansToParentsStep3PermissionDenied() {
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150'));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld112->addItem($c_fld113);

        $itemFactory =& new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = array(150 => false, 112 => $fld112, 113 => $fld113);
        $orphans  = array(150 => 150, 112 => 112);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array(150 => 150, 112 => 112));
        $this->assertEqual($itemList, array(150 => false, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    /**
     * 140
     * |-- 150
     * |   |-- 112
     * |   |   `-- 113
     * |   |       `-- *
     * |   `-- 115
     * |       `-- *
     * `-- 135
     *     `-- *
     *
     * Find path to root for 113, 115 & 135
     */
    function testBuildTreeFromLeavesMultipleStep1() {
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));
        $fld115 =& new Docman_Folder(array('item_id' => 115, 'parent_id' => 150,'title' => 'Folder 115'));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'parent_id' => 140,'title' => 'Folder 135'));

        $itemFactory =& new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113,
                          115 => $fld115,
                          135 => $fld135);
        $orphans = array(113 => 113,
                         115 => 115,
                         135 => 135);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(112, 150, 140));
        $this->assertEqual($orphans, array(113 => 113, 115 => 115, 135 => 135));
        $this->assertEqual($itemList, array(113 => $fld113, 115 => $fld115, 135 => $fld135));
        $this->assertFalse($rootId);
    }


    function testBuildTreeFromLeavesMultipleStep2() {
        $fld140 =& new Docman_Folder(array('item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 =& new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150', 'rank' => -2));
        $fld112 =& new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112', 'rank' => -2));
        $fld113 =& new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113', 'rank' => 0));
        $fld115 =& new Docman_Folder(array('item_id' => 115, 'parent_id' => 150,'title' => 'Folder 115', 'rank' => -1));
        $fld135 =& new Docman_Folder(array('item_id' => 135, 'parent_id' => 140,'title' => 'Folder 135', 'rank' => -1));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld140 = $fld140;
        $c_fld115 = $fld115;
        $c_fld135 = $fld135;
        $c_fld140->addItem($c_fld150);
        $c_fld150->addItem($c_fld112);
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld115);
        $c_fld140->addItem($c_fld135);

        $itemFactory =& new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113,
                          115 => $fld115,
                          150 => $fld150,
                          140 => $fld140,
                          135 => $fld135,
                          112 => $fld112);
        // It's not very clean but the orphan order is very important to make
        // the test pass. To avoid the pain to develop a tree comparator, we rely
        // on the array/object comparison of SimpleTest. The bad news comes with
        // PrioritizeList because it store a mapping between it's elements and
        // the priorities. While the final result will always be the same
        // (items ordered by priority) the internal status of the mapping may
        // differ. And this internal difference will break tests :/
        $orphans = array(140 => 140,
                         150 => 150,
                         112 => 112,
                         113 => 113,
                         115 => 115,
                         135 => 135);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array());
        $this->assertEqual($itemList, array(113 => $c_fld113, 115 => $c_fld115, 135 => $c_fld135, 112 => $c_fld112, 140 => $c_fld140, 150 => $c_fld150));
        $this->assertEqual($rootId, 140);
    }

}
?>
