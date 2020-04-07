<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class MetadataListOfValuesElementFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCloneValues(): void
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
        $this->assertEquals(201, $valuesMapping[101]);
        $this->assertEquals(202, $valuesMapping[102]);
    }

    /**
     * 2 values + None in the source, no values in the destination.
     * 2 values must be created in the dest
     */
    public function testExportValuesWithEmptyDest(): void
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
    public function testExportValuesWithNonEmptyDest(): void
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
