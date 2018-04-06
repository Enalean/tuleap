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

class MetadataListOfValuesElementFactoryTest extends TuleapTestCase {

    function testCloneValues()
    {
        // Factory to test
        $srcLoveF = \Mockery::mock(Docman_MetadataListOfValuesElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // Parameters
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // List of src elements
        $loveArray[0] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->allows(['getId' => 100]);
        $loveArray[1] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->allows(['getId' => 101]);
        $loveArray[2] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->allows(['getId' => 102]);
        $srcLoveF->expects()->getListByFieldId(123, 'field_123', false)->andReturns($loveArray);

        // Actions in the dst factory
        $dstLoveF = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->shouldReceive('create')->andReturns(201)->once();
        $dstLoveF->shouldReceive('create')->andReturns(202)->once();
        $srcLoveF->expects()->getMetadataListOfValuesElementFactory(321)->andReturns($dstLoveF);

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
        $srcLoveF = \Mockery::mock(Docman_MetadataListOfValuesElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // Parameters
        $valuesMapping = array();
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->allows(['getId' => 100]);
        $loveArray[1] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->allows(['getId' => 101]);
        $loveArray[2] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->allows(['getId' => 102]);
        $srcLoveF->shouldReceive('getListByFieldId')->andReturns($loveArray);

        $dstLoveF = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->shouldReceive('create')->times(2);
        $srcLoveF->allows(['getMetadataListOfValuesElementFactory' => $dstLoveF]);

        $srcLoveF->exportValues($srcMd, $dstMd, $valuesMapping);
    }

    /**
     * 2 values + None in the source, one already exists in destination.
     * 1 value must be create and 1 updated
     */
    function testExportValuesWithNonEmptyDest()
    {
        // Factory to test
        $srcLoveF = \Mockery::mock(Docman_MetadataListOfValuesElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        // Parameters
        $valuesMapping = array(101 => 201);
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->allows(['getId' => 100]);
        $loveArray[1] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->allows(['getId' => 101]);
        $loveArray[2] = \Mockery::spy(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->allows(['getId' => 102]);
        $srcLoveF->shouldReceive('getListByFieldId')->andReturns($loveArray);

        $dstLoveF = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->shouldReceive('create')->once();
        $dstLoveF->shouldReceive('update')->once();
        $srcLoveF->allows(['getMetadataListOfValuesElementFactory' => $dstLoveF]);

        $srcLoveF->exportValues($srcMd, $dstMd, $valuesMapping);
    }
}
