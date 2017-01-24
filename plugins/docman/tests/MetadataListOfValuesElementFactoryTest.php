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

require_once 'bootstrap.php';

Mock::generate('Docman_MetadataListOfValuesElementFactory');
Mock::generate('Docman_MetadataListOfValuesElement');

Mock::generatePartial('Docman_MetadataListOfValuesElementFactory', 'MetadataListOfValuesElementFactoryMocked', array('getListByFieldId', 'getMetadataListOfValuesElementFactory'));

class MetadataListOfValuesElementFactoryTest extends TuleapTestCase {

    function testCloneValues()
    {
        // Factory to test
        $srcLoveF = new MetadataListOfValuesElementFactoryMocked($this);

        // Parameters
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // List of src elements
        $loveArray[0] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[0]->setReturnValue('getId', 100);
        $loveArray[1] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[1]->setReturnValue('getId', 101);
        $loveArray[2] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[2]->setReturnValue('getId', 102);
        $srcLoveF->setReturnValue('getListByFieldId', $loveArray);
        $srcLoveF->expectOnce('getListByFieldId', array(123, 'field_123', false));

        // Actions in the dst factory
        $dstLoveF = new MockDocman_MetadataListOfValuesElementFactory($this);
        $dstLoveF->expectCallCount('create', 2);
        $dstLoveF->setReturnValueAt(0, 'create', 201);
        $dstLoveF->setReturnValueAt(1, 'create', 202);
        $srcLoveF->setReturnReference('getMetadataListOfValuesElementFactory', $dstLoveF);
        $srcLoveF->expectOnce('getMetadataListOfValuesElementFactory', array(321));

        // Run the test
        $valuesMapping = $srcLoveF->cloneValues($srcMd, $dstMd);
        $this->assertEqual($valuesMapping[101], 201);
        $this->assertEqual($valuesMapping[102], 202);
    }

    /**
     * 2 values + None in the source, no values in the destination.
     * 2 values must be created in the dest
     */
    function testExportValuesWithEmptyDest()
    {
        // Factory to test
        $srcLoveF = new MetadataListOfValuesElementFactoryMocked($this);

        // Parameters
        $valuesMapping = array();
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[0]->setReturnValue('getId', 100);
        $loveArray[1] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[1]->setReturnValue('getId', 101);
        $loveArray[2] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[2]->setReturnValue('getId', 102);
        $srcLoveF->setReturnValue('getListByFieldId', $loveArray);
        $srcLoveF->expectOnce('getListByFieldId');

        $dstLoveF = new MockDocman_MetadataListOfValuesElementFactory($this);
        $dstLoveF->expectCallCount('create', 2);
        $srcLoveF->setReturnReference('getMetadataListOfValuesElementFactory', $dstLoveF);

        $srcLoveF->exportValues($srcMd, $dstMd, $valuesMapping);
    }

    /**
     * 2 values + None in the source, one already exists in destination.
     * 1 value must be create and 1 updated
     */
    function testExportValuesWithNonEmptyDest()
    {
        // Factory to test
        $srcLoveF = new MetadataListOfValuesElementFactoryMocked($this);

        // Parameters
        $valuesMapping = array(101 => 201);
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[0]->setReturnValue('getId', 100);
        $loveArray[1] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[1]->setReturnValue('getId', 101);
        $loveArray[2] = new MockDocman_MetadataListOfValuesElement($this);
        $loveArray[2]->setReturnValue('getId', 102);
        $srcLoveF->setReturnValue('getListByFieldId', $loveArray);
        $srcLoveF->expectOnce('getListByFieldId');

        $dstLoveF = new MockDocman_MetadataListOfValuesElementFactory($this);
        $dstLoveF->expectCallCount('create', 1);
        $dstLoveF->expectCallCount('update', 1);
        $srcLoveF->setReturnReference('getMetadataListOfValuesElementFactory', $dstLoveF);

        $srcLoveF->exportValues($srcMd, $dstMd, $valuesMapping);
    }
}
