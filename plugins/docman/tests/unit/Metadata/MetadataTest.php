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

namespace Tuleap\Docman\Metadata;

use ArrayIterator;
use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElementFactory;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataTest extends TestCase
{
    public function testCloneOneMetadata(): void
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd      = new Docman_Metadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadataMapping = [];

        // Factory to test
        $srcMdF = $this->createPartialMock(Docman_MetadataFactory::class, [
            '_getMetadataFactory',
            '_getListOfValuesElementFactory',
        ]);

        $dstMdF = $this->createMock(Docman_MetadataFactory::class);
        $dstMdF->expects($this->once())->method('create')->willReturn(401);

        $iter = new ArrayIterator();
        $dstMdF->method('findByName')->willReturn($iter);
        $srcMdF->expects($this->once())->method('_getMetadataFactory')->with($dstGroupId)->willReturn($dstMdF);

        $srcMdF->expects($this->never())->method('_getListOfValuesElementFactory');

        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        self::assertEquals(401, $metadataMapping['md'][301]);
    }

    public function testCloneOneMetadataList(): void
    {
        // Parameters
        $dstGroupId = '321';
        $srcMd      = new Docman_ListMetadata();
        $srcMd->setId(301);
        $srcMd->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadataMapping = [];

        // Factory to test
        $srcMdF = $this->createPartialMock(Docman_MetadataFactory::class, [
            '_getMetadataFactory',
            '_getListOfValuesElementFactory',
        ]);

        $dstMdF = $this->createMock(Docman_MetadataFactory::class);
        $dstMdF->expects($this->once())->method('create')->willReturn(401);

        $iter = new ArrayIterator();
        $dstMdF->method('findByName')->willReturn($iter);
        $srcMdF->expects($this->once())->method('_getMetadataFactory')->with($dstGroupId)->willReturn($dstMdF);

        $dstLoveF      = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $valuesMapping = [101 => 201, 102 => 202];
        $dstLoveF->expects($this->once())->method('cloneValues')->willReturn($valuesMapping);
        $srcMdF->expects($this->once())->method('_getListOfValuesElementFactory')->with(301)->willReturn($dstLoveF);

        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd, $metadataMapping);
        self::assertEquals(401, $metadataMapping['md'][301]);
        self::assertEquals(201, $metadataMapping['love'][101]);
        self::assertEquals(202, $metadataMapping['love'][102]);
    }

    /**
     * Ensure that data from one call doesn't override data of the second call
     */
    public function testTwoCallsOfCloneOneMetadataList(): void
    {
        // Common params
        $dstGroupId              = '321';
        $metadataMapping         = [];
        $metadataMapping['love'] = [];

        $findIter = new ArrayIterator();

        // Factory to test
        $srcMdF = $this->createPartialMock(Docman_MetadataFactory::class, [
            '_getMetadataFactory',
            '_getListOfValuesElementFactory',
        ]);

        // First Call setup
        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $dstMdF1 = $this->createMock(Docman_MetadataFactory::class);
        $dstMdF1->expects($this->once())->method('create')->willReturn(401);
        $dstMdF1->method('findByName')->willReturn($findIter);

        $dstLoveF1 = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF1->method('cloneValues')->willReturn([101 => 201, 102 => 202]);

        // Second call setup
        $srcMd2 = new Docman_ListMetadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $dstMdF2 = $this->createMock(Docman_MetadataFactory::class);
        $dstMdF2->expects($this->once())->method('create')->willReturn(402);
        $dstMdF2->method('findByName')->willReturn($findIter);

        $dstLoveF2 = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF2->method('cloneValues')->willReturn([103 => 203, 104 => 204]);

        $srcMdF->expects($this->exactly(2))->method('_getMetadataFactory')->willReturnOnConsecutiveCalls($dstMdF1, $dstMdF2);
        $srcMdF->expects($this->exactly(2))->method('_getListOfValuesElementFactory')->willReturnOnConsecutiveCalls($dstLoveF1, $dstLoveF2);

        // Run test
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd1, $metadataMapping);
        $srcMdF->_cloneOneMetadata($dstGroupId, $srcMd2, $metadataMapping);

        self::assertEquals(401, $metadataMapping['md'][301]);
        self::assertEquals(402, $metadataMapping['md'][302]);
        self::assertEquals(201, $metadataMapping['love'][101]);
        self::assertEquals(202, $metadataMapping['love'][102]);
        self::assertEquals(203, $metadataMapping['love'][103]);
        self::assertEquals(204, $metadataMapping['love'][104]);
    }

    public function testCloneRealMetadata(): void
    {
        // Parameters
        $dstGroupId      = '321';
        $metadataMapping = [];

        // Factory to test
        $srcMdF = $this->createPartialMock(Docman_MetadataFactory::class, [
            'getRealMetadataList',
            '_cloneOneMetadata',
        ]);

        $srcMd1 = new Docman_ListMetadata();
        $srcMd1->setId(301);
        $srcMd1->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $srcMd2 = new Docman_Metadata();
        $srcMd2->setId(302);
        $srcMd2->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $srcMdF->expects($this->once())->method('getRealMetadataList')->with(false)->willReturn([$srcMd1, $srcMd2]);
        $matcher = self::exactly(2);

        $srcMdF->expects($matcher)->method('_cloneOneMetadata')->willReturnCallback(function (...$parameters) use ($matcher, $dstGroupId, $srcMd1, $metadataMapping, $srcMd2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($dstGroupId, $parameters[0]);
                self::assertSame($srcMd1, $parameters[1]);
                self::assertSame($metadataMapping, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($dstGroupId, $parameters[0]);
                self::assertSame($srcMd2, $parameters[1]);
                self::assertSame($metadataMapping, $parameters[2]);
            }
        });

        // Run the test
        $srcMdF->_cloneRealMetadata($dstGroupId, $metadataMapping);
    }
}
