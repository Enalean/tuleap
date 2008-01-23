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
    
    // Test no wiki page case.
    function testNoWikiPage () {
        $mockDao =& new MockDocman_ItemDao($this);
        $mockDao->setReturnValue('searchById', null);
		
        $itemFactory =& new Docman_ItemFactoryTestVersion($this);
        $itemFactory->setReturnReference('_getItemDao', $mockDao);
		
        $this->assertIdentical($itemFactory->getWikiPageName(50), null);
        $this->assertIdentical($itemFactory->getWikiPageName(30), null);
        $this->assertIdentical($itemFactory->getWikiPageName(0), null);
    }
	
    // Test wiki page exist for docman item
    function testWikiPageExists () {
        $dar =& new MockDataAccessResult($this);
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('rowCount', 1);
        $dar->setReturnValue('current', array('wiki_page' => 'TranscludeTest'));

        $mockDao = new MockDocman_ItemDao($this);
        $mockDao->setReturnReference('searchById', $dar);

        $itemFactory =& new Docman_ItemFactoryTestVersion($this);
        $itemFactory->setReturnReference('_getItemDao', $mockDao);

        $this->assertIdentical($itemFactory->getWikiPageName(25), 'TranscludeTest');
    }
}
?>