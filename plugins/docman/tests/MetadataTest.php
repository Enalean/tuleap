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

Mock::generate('EventManager');

Mock::generate('ArrayIterator');

Mock::generate('Docman_MetadataListOfValuesElementFactory');
Mock::generate('Docman_MetadataFactory');
Mock::generatePartial('Docman_MetadataFactory', 'MetadataFactoryMockedForCloneOneMd', array('_getMetadataFactory', '_getListOfValuesElementFactory', '_getEventManager'));
Mock::generatePartial('Docman_MetadataFactory', 'MetadataFactoryMockedForCloneRealMd', array('getRealMetadataList', '_cloneOneMetadata'));

class MetadataTest extends TuleapTestCase {

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
        $srcMdF->expectAt(0, '_cloneOneMetadata', array($dstGroupId, $srcMd1, $metadataMapping));
        $srcMdF->expectAt(1, '_cloneOneMetadata', array($dstGroupId, $srcMd2, $metadataMapping));

        // Run the test
        $srcMdF->_cloneRealMetadata($dstGroupId, $metadataMapping);
    }
}
