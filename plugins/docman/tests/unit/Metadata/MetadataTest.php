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
class MetadataTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCloneOneMetadata(): void
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd = new Docman_Metadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadataMapping = array();

        // Factory to test
        $srcMdF = \Mockery::mock(Docman_MetadataFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $eventManager = \Mockery::spy(EventManager::class);
        $srcMdF->shouldReceive('_getEventManager')->andReturns($eventManager);

        $dstMdF = \Mockery::spy(Docman_MetadataFactory::class);
        $dstMdF->shouldReceive('create')->once()->andReturns(401);

        $iter = \Mockery::spy(ArrayIterator::class);
        $iter->shouldReceive('count')->andReturns(0);
        $dstMdF->shouldReceive('findByName')->andReturns($iter);
        $srcMdF->shouldReceive('_getMetadataFactory')->with($dstGroupId)->once()->andReturns($dstMdF);

        $srcMdF->shouldReceive('_getListOfValuesElementFactory')->never();

        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        $this->assertEquals(401, $metadataMapping['md'][301]);
    }

    public function testCloneOneMetadataList(): void
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadataMapping = array();

        // Factory to test
        $srcMdF = \Mockery::mock(Docman_MetadataFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $eventManager = \Mockery::spy(EventManager::class);
        $srcMdF->shouldReceive('_getEventManager')->andReturns($eventManager);

        $dstMdF = \Mockery::spy(Docman_MetadataFactory::class);
        $dstMdF->shouldReceive('create')->once()->andReturns(401);

        $iter = \Mockery::spy(ArrayIterator::class);
        $iter->shouldReceive('count')->andReturns(0);
        $dstMdF->shouldReceive('findByName')->andReturns($iter);
        $srcMdF->shouldReceive('_getMetadataFactory')->with($dstGroupId)->once()->andReturns($dstMdF);

        $dstLoveF = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $valuesMapping = array(101 => 201, 102 => 202);
        $dstLoveF->shouldReceive('cloneValues')->once()->andReturns($valuesMapping);
        $srcMdF->shouldReceive('_getListOfValuesElementFactory')->with(301)->once()->andReturns($dstLoveF);

        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        $this->assertEquals(401, $metadataMapping['md'][301]);
        $this->assertEquals(201, $metadataMapping['love'][101]);
        $this->assertEquals(202, $metadataMapping['love'][102]);
    }

    /**
     * Ensure that data from one call doesn't override data of the second call
     */
    public function testTwoCallsOfCloneOneMetadataList(): void
    {
        // Common params
        $dstGroupId = '321';
        $metadataMapping = array();
        $metadataMapping['love'] = array();

        $findIter = \Mockery::spy(ArrayIterator::class);
        $findIter->shouldReceive('count')->andReturns(0);

        // Factory to test
        $srcMdF = \Mockery::mock(Docman_MetadataFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $eventManager = \Mockery::spy(EventManager::class);
        $srcMdF->shouldReceive('_getEventManager')->andReturns($eventManager);

        // First Call setup
        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $dstMdF1 = \Mockery::spy(Docman_MetadataFactory::class);
        $dstMdF1->shouldReceive('create')->once()->andReturns(401);
        $dstMdF1->shouldReceive('findByName')->andReturns($findIter);
        $srcMdF->shouldReceive('_getMetadataFactory')->once()->andReturns($dstMdF1);

        $dstLoveF1 = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF1->shouldReceive('cloneValues')->andReturns(array(101 => 201, 102 => 202));
        $srcMdF->shouldReceive('_getListOfValuesElementFactory')->once()->andReturns($dstLoveF1);

        // Second call setup
        $srcMd2 = new Docman_ListMetadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $dstMdF2 = \Mockery::spy(Docman_MetadataFactory::class);
        $dstMdF2->shouldReceive('create')->once()->andReturns(402);
        $dstMdF2->shouldReceive('findByName')->andReturns($findIter);
        $srcMdF->shouldReceive('_getMetadataFactory')->once()->andReturns($dstMdF2);

        $dstLoveF2 = \Mockery::spy(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF2->shouldReceive('cloneValues')->andReturns(array(103 => 203, 104 => 204));
        $srcMdF->shouldReceive('_getListOfValuesElementFactory')->once()->andReturns($dstLoveF2);

        // Run test
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd1, $metadataMapping);
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd2, $metadataMapping);

        $this->assertEquals(401, $metadataMapping['md'][301]);
        $this->assertEquals(402, $metadataMapping['md'][302]);
        $this->assertEquals(201, $metadataMapping['love'][101]);
        $this->assertEquals(202, $metadataMapping['love'][102]);
        $this->assertEquals(203, $metadataMapping['love'][103]);
        $this->assertEquals(204, $metadataMapping['love'][104]);
    }

    public function testCloneRealMetadata(): void
    {
        // Parameters
        $dstGroupId = '321';
        $metadataMapping = array();

        // Factory to test
        $srcMdF = \Mockery::mock(Docman_MetadataFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $srcMd2 = new Docman_Metadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $srcMdF->shouldReceive('getRealMetadataList')->with(false)->once()->andReturns(array($srcMd1, $srcMd2));

        $srcMdF->shouldReceive('_cloneOneMetadata')->times(2);
        $srcMdF->shouldReceive('_cloneOneMetadata')->with($dstGroupId, $srcMd1, $metadataMapping)->ordered();
        $srcMdF->shouldReceive('_cloneOneMetadata')->with($dstGroupId, $srcMd2, $metadataMapping)->ordered();

        // Run the test
        $srcMdF->_cloneRealMetadata($dstGroupId, $metadataMapping);
    }
}
