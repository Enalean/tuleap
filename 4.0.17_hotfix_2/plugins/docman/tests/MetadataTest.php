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

require_once(dirname(__FILE__).'/../include/Docman_MetadataFactory.class.php');
//require_once(dirname(__FILE__).'/../include/Docman_MetadataValueFactory.class.php');
//require_once(dirname(__FILE__).'/../include/Docman_ItemFactory.class.php');

//Mock::generate('Docman_MetadataDao');

//require_once('common/dao/include/DataAccessResult.class.php');
//Mock::generate('DataAccessResult');

//require_once('BaseLanguage.class.php');
//Mock::generate('BaseLanguage');

require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

Mock::generate('ArrayIterator');

Mock::generate('Docman_MetadataListOfValuesElementFactory');
Mock::generate('Docman_MetadataFactory');
Mock::generatePartial('Docman_MetadataFactory', 'MetadataFactoryMockedForCloneOneMd', array('_getMetadataFactory', '_getListOfValuesElementFactory', '_getEventManager'));
Mock::generatePartial('Docman_MetadataFactory', 'MetadataFactoryMockedForCloneRealMd', array('getRealMetadataList', '_cloneOneMetadata'));

class MetadataTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function __construct($name = 'Docman_Metadata test')
    {
        parent::__construct($name);
        //$this->groupId = 1540;
    }

    function testCloneOneMetadata()
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd = new Docman_Metadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadataMapping = array();
        

        // Factory to test
        $srcMdF = new MetadataFactoryMockedForCloneOneMd($this);

        $eventManager = new MockEventManager($this);
        $srcMdF->setReturnReference('_getEventManager', $eventManager);

        $dstMdF = new MockDocman_MetadataFactory($this);
        $dstMdF->setReturnValue('create', 401);
        $dstMdF->expectOnce('create');

        $iter = new MockArrayIterator($this);
        $iter->setReturnValue('count', 0);
        $dstMdF->setReturnValue('findByName', $iter);

        $srcMdF->setReturnReference('_getMetadataFactory', $dstMdF);
        $srcMdF->expectOnce('_getMetadataFactory', array($dstGroupId));
        
        $srcMdF->expectNever('_getListOfValuesElementFactory');
        
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        $this->assertEqual($metadataMapping['md'][301], 401);
        
        $srcMdF->tally();
        $dstMdF->tally();
    }
    
    function testCloneOneMetadataList()
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadataMapping = array();
        
        // Factory to test
        $srcMdF = new MetadataFactoryMockedForCloneOneMd($this);

        $eventManager = new MockEventManager($this);
        $srcMdF->setReturnReference('_getEventManager', $eventManager);

        $dstMdF = new MockDocman_MetadataFactory($this);
        $dstMdF->setReturnValue('create', 401);
        $dstMdF->expectOnce('create');

        $iter = new MockArrayIterator($this);
        $iter->setReturnValue('count', 0);
        $dstMdF->setReturnValue('findByName', $iter);

        $srcMdF->setReturnReference('_getMetadataFactory', $dstMdF);
        $srcMdF->expectOnce('_getMetadataFactory', array($dstGroupId));
        
        $dstLoveF = new MockDocman_MetadataListOfValuesElementFactory($this);
        $valuesMapping = array(101 => 201, 102 => 202);
        $dstLoveF->expectOnce('cloneValues');
        $dstLoveF->setReturnValue('cloneValues', $valuesMapping);
        $srcMdF->setReturnReference('_getListOfValuesElementFactory', $dstLoveF);
        $srcMdF->expectOnce('_getListOfValuesElementFactory',  array(301));
        
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        $this->assertEqual($metadataMapping['md'][301], 401);
        $this->assertEqual($metadataMapping['love'][101], 201);
        $this->assertEqual($metadataMapping['love'][102], 202);
        
        $dstLoveF->tally();
        $srcMdF->tally();
        $dstMdF->tally();
    }
    
    /**
     * Ensure that data from one call doesn't override data of the second call
     */
    function testTwoCallsOfCloneOneMetadataList()
    {
        // Common params
        $dstGroupId = '321';
        $metadataMapping = array();
        $metadataMapping['love'] = array();

        $findIter = new MockArrayIterator($this);
        $findIter->setReturnValue('count', 0);

        // Factory to test
        $srcMdF = new MetadataFactoryMockedForCloneOneMd($this);
        
        $eventManager = new MockEventManager($this);
        $srcMdF->setReturnReference('_getEventManager', $eventManager);

        // First Call setup
        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        
        $dstMdF1 = new MockDocman_MetadataFactory($this);
        $dstMdF1->setReturnValue('create', 401);
        $dstMdF1->expectOnce('create');
        $dstMdF1->setReturnValue('findByName', $findIter);
        $srcMdF->setReturnReferenceAt(0, '_getMetadataFactory', $dstMdF1);

        $dstLoveF1 = new MockDocman_MetadataListOfValuesElementFactory($this);
        $dstLoveF1->setReturnValue('cloneValues', array(101 => 201, 102 => 202));
        $srcMdF->setReturnReferenceAt(0, '_getListOfValuesElementFactory', $dstLoveF1);
        
        // Second call setup
        $srcMd2 = new Docman_ListMetadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        
        $dstMdF2 = new MockDocman_MetadataFactory($this);
        $dstMdF2->setReturnValue('create', 402);
        $dstMdF2->expectOnce('create');
        $dstMdF2->setReturnValue('findByName', $findIter);
        $srcMdF->setReturnReferenceAt(1, '_getMetadataFactory', $dstMdF2);
        
        $dstLoveF2 = new MockDocman_MetadataListOfValuesElementFactory($this);
        $dstLoveF2->setReturnValue('cloneValues', array(103 => 203, 104 => 204));
        $srcMdF->setReturnReferenceAt(1, '_getListOfValuesElementFactory', $dstLoveF2);
        
        // Run test
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd1, $metadataMapping);
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd2, $metadataMapping);

        $this->assertEqual($metadataMapping['md'][301], 401);
        $this->assertEqual($metadataMapping['md'][302], 402);
        $this->assertEqual($metadataMapping['love'][101], 201);
        $this->assertEqual($metadataMapping['love'][102], 202);
        $this->assertEqual($metadataMapping['love'][103], 203);
        $this->assertEqual($metadataMapping['love'][104], 204);
    }

    function testCloneRealMetadata()
    {
        // Parameters
        $dstGroupId = '321';
        $metadataMapping = array();
        
        // Factory to test
        $srcMdF = new MetadataFactoryMockedForCloneRealMd($this);

        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $srcMd2 = new Docman_Metadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $srcMdF->setReturnValue('getRealMetadataList', array($srcMd1, $srcMd2));
        $srcMdF->expectOnce('getRealMetadataList', array(false));
        
        $srcMdF->expectCallCount('_cloneOneMetadata', 2);
        $srcMdF->expectArgumentsAt(0, '_cloneOneMetadata', array($dstGroupId, $srcMd1, $metadataMapping));
        $srcMdF->expectArgumentsAt(1, '_cloneOneMetadata', array($dstGroupId, $srcMd2, $metadataMapping));
        
        // Run the test
        $srcMdF->_cloneRealMetadata($dstGroupId, $metadataMapping);
        $srcMdF->tally();
    }
    
    /*
     * MV: Comment all these tests, metadata changed a lot and new tests should be rewritten.
     *
    function &getHardCodedMetadataList($param) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        
        // no status and obso date because require DB access
        $hcmdlabels = array('title', 'description', 'owner'
                            , 'create_date', 'update_date');
        foreach($hcmdlabels as $label) {
            $mdhca[] = $mdFactory->getHardCodedMetadataFromLabel($label);
        }

        return $mdhca;
    }

    function &getDao() {
        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('valid', false);
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValueAt(1, 'valid', true);

        $md1 = array('field_id' => 32,
                     'group_id' => $this->groupId,
                     'name' => 'Comments',
                     'data_type' => PLUGIN_DOCMAN_METADATA_TYPE_TEXT,
                     'label' => 'field_32',
                     'description' => 'A set of comments',
                     'required' => false,
                     'empty_ok' => true,
                     'special' => false,
                     'default_value' => '',
                     'use_it' => PLUGIN_DOCMAN_METADATA_USED);

        $md2 = array('field_id' => 75,
                     'group_id' => $this->groupId,
                     'name' => 'Publication date',
                     'data_type' => PLUGIN_DOCMAN_METADATA_TYPE_DATE,
                     'label' => 'field_75',
                     'description' => 'pub date',
                     'required' => false,
                     'empty_ok' => true,
                     'special' => false,
                     'default_value' => '',
                     'use_it' => PLUGIN_DOCMAN_METADATA_USED);

        $dar->setReturnValueAt(0, 'current', $md1);
        $dar->setReturnValueAt(1, 'current', $md2);

        $dao = new MockDocman_MetadataDao($this);
        $dao->setReturnValue('searchByGroupId', $dar);
        return $dao;
    }

    function &_createFromRow($row) {
        $mdFactory = new Docman_MetadataFactory($this->groupId);
        $md =& $mdFactory->_createFromRow($row);
        return $md;
    }

    function &getRealMetadataList($onlyUsed = false, $type = array()) {
        $mda = array();

        $dao =& $this->getDao();
        $dar = $dao->searchByGroupId($this->groupId, $onlyUsed, $type);
        while($dar->valid()) {
            $row =& $dar->current();                        
            
            $mda[] =& $this->_createFromRow($row);

            $dar->next();
        }

        return $mda;
    }


    // Adatation of Docman_MetadataFactory::appendItemMetadataList
    // to avoid DB queries
    function appendItemMetadataList(&$item) {
        $mda =& $this->getHardCodedMetadataList(true);
        
        $i = 0;
        $nbMd = count($mda);
        while($i < $nbMd) {
            $mda[$i]->setValue($item->getHardCodedMetadataValue($mda[$i]->getLabel()));
            $i++;
        }

        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        $mdareal =& $this->getRealMetadataList(true);
        $j = 0;
        $nbMd = count($mdareal);
        while($j < $nbMd) {
            $mdv =& $mdvFactory->getMetadataValue($item, $mdareal[$j]);
            if($mdv === null) {
                // create an empty one
                $mdv = $mdvFactory->newMetadataValue($item->getId(),
                                                     $mdareal[$j]->getId(),
                                                     $mdareal[$j]->getType(),
                                                     null);
            }
            $mdareal[$j]->setValue($mdv->getValue());
            $mda[$i] =& $mdareal[$j];
            $i++;            
            $j++;
        }

        $item->setMetadata($mda);
    }

    //
    // This test is to verify the behaviour of PHP with multiple arrays,
    // references, objects, ...
    // Manuel Vacelet encountered troubles with these functions with php 4.3.10 that
    // lead to a crash (core dump) of apache.
    //
    function testMetadataCreationFromUserInput() {
        $GLOBALS['Language'] =& new MockBaseLanguage();
        $t = new MockDataAccessResult();
        // Item creation
        $i = array('group_id' => $this->groupId,
                   'parent_id' => 1,
                   'title' => 'Subfolder',
                   'description' => 'One subfolder',
                   'user_id' => 104,
                   'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                   'link_url' => 'http://php.net');

        $itemFactory = new Docman_ItemFactory();
        $item = $itemFactory->getItemFromRow($i);

        $this->appendItemMetadataList($item);                   

        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        
        $metadataArray = array('field_75' => '2006-4-2');

        //------> Original code
        $mdIter =& $item->getMetadataIterator();
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();
            if(isset($metadataArray[$md->getLabel()])) {
                $mdv = $mdvFactory->newMetadataValue($item->getId(), $md->getId(), $md->getType(), $metadataArray[$md->getLabel()]);
                $md->setValue($mdv->getValue());
            }
            $mdIter->next();
        }
        //<------ Original code
    
        /// TESTS
        $mdIter =& $item->getMetadataIterator();
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();

            if($md->getId() == 75) {
                $this->assertEqual($md->getValue(), '2006-4-2');
            }

            $mdIter->next();
        }

    }
*/
}
?>
