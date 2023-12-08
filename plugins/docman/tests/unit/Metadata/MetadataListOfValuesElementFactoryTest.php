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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class MetadataListOfValuesElementFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testCloneValues(): void
    {
        // Factory to test
        $metadata_list_of_values_element_factory = $this->getMockBuilder(Docman_MetadataListOfValuesElementFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getListByFieldId', 'getMetadataListOfValuesElementFactory'])
            ->getMock();

        // Parameters
        $srcMd = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // List of src elements
        $loveArray[0] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->method('getId')->willReturn(100);
        $loveArray[1] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->method('getId')->willReturn(101);
        $loveArray[2] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->method('getId')->willReturn(102);
        $metadata_list_of_values_element_factory->method('getListByFieldId')->with(123, 'field_123', false)->willReturn($loveArray);

        // Actions in the dst factory
        $dstLoveF = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->method('create')->willReturnOnConsecutiveCalls(201, 202);
        $metadata_list_of_values_element_factory->method('getMetadataListOfValuesElementFactory')->with(321)->willReturn($dstLoveF);

        // Run the test
        $valuesMapping = $metadata_list_of_values_element_factory->cloneValues($srcMd, $dstMd);
        self::assertEquals(201, $valuesMapping[101]);
        self::assertEquals(202, $valuesMapping[102]);
    }

    /**
     * 2 values + None in the source, no values in the destination.
     * 2 values must be created in the dest
     */
    public function testExportValuesWithEmptyDest(): void
    {
        // Factory to test
        $metadata_list_of_values_element_factory = $this->getMockBuilder(Docman_MetadataListOfValuesElementFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getListByFieldId', 'getMetadataListOfValuesElementFactory'])
            ->getMock();

        // Parameters
        $valuesMapping = [];
        $srcMd         = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->method('getId')->willReturn(100);
        $loveArray[0]->method('setRank');
        $loveArray[1] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->method('getId')->willReturn(101);
        $loveArray[1]->method('setRank');
        $loveArray[2] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->method('getId')->willReturn(102);
        $loveArray[2]->method('setRank');
        $metadata_list_of_values_element_factory->method('getListByFieldId')->willReturn($loveArray);

        $dstLoveF = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->expects(self::exactly(2))->method('create');
        $metadata_list_of_values_element_factory->method('getMetadataListOfValuesElementFactory')->willReturn($dstLoveF);

        $metadata_list_of_values_element_factory->exportValues($srcMd, $dstMd, $valuesMapping);
    }

    /**
     * 2 values + None in the source, one already exists in destination.
     * 1 value must be create and 1 updated
     */
    public function testExportValuesWithNonEmptyDest(): void
    {
        // Factory to test
        $metadata_list_of_values_element_factory = $this->getMockBuilder(Docman_MetadataListOfValuesElementFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getListByFieldId', 'getMetadataListOfValuesElementFactory'])
            ->getMock();

        // Parameters
        $valuesMapping = [101 => 201];
        $srcMd         = new Docman_ListMetadata();
        $srcMd->setId(123);
        $srcMd->setLabel('field_123');
        $dstMd = new Docman_ListMetadata();
        $dstMd->setId(321);

        // Src elements
        $loveArray[0] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[0]->method('getId')->willReturn(100);
        $loveArray[0]->method('setId');
        $loveArray[0]->method('setRank');
        $loveArray[1] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[1]->method('getId')->willReturn(101);
        $loveArray[1]->method('setId');
        $loveArray[1]->method('setRank');
        $loveArray[2] = $this->createMock(Docman_MetadataListOfValuesElement::class);
        $loveArray[2]->method('getId')->willReturn(102);
        $loveArray[2]->method('setId');
        $loveArray[2]->method('setRank');
        $metadata_list_of_values_element_factory->method('getListByFieldId')->willReturn($loveArray);

        $dstLoveF = $this->createMock(Docman_MetadataListOfValuesElementFactory::class);
        $dstLoveF->expects(self::once())->method('create');
        $dstLoveF->expects(self::once())->method('update');
        $metadata_list_of_values_element_factory->method('getMetadataListOfValuesElementFactory')->willReturn($dstLoveF);

        $metadata_list_of_values_element_factory->exportValues($srcMd, $dstMd, $valuesMapping);
    }

    public function testItReturnsTheListOfAllStatus(): void
    {
        $metadata_list_of_values_element_factory = new Docman_MetadataListOfValuesElementFactory();

        $statuses = $metadata_list_of_values_element_factory->getStatusList();

        self::assertCount(4, $statuses);
        self::assertArrayHasKey(PLUGIN_DOCMAN_ITEM_STATUS_NONE, $statuses);
        self::assertArrayHasKey(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT, $statuses);
        self::assertArrayHasKey(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED, $statuses);
        self::assertArrayHasKey(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED, $statuses);
    }

    /**
     * @dataProvider dataProviderDocmanStatus
     */
    public function testItReturnsTheExpectedStatus(int $status_id): void
    {
        $metadata_list_of_values_element_factory = new Docman_MetadataListOfValuesElementFactory();

        $status = $metadata_list_of_values_element_factory->getStatusList($status_id);

        self::assertSame($status_id, $status->getId());
    }

    public function dataProviderDocmanStatus(): array
    {
        return [
            [PLUGIN_DOCMAN_ITEM_STATUS_NONE],
            [PLUGIN_DOCMAN_ITEM_STATUS_DRAFT],
            [PLUGIN_DOCMAN_ITEM_STATUS_APPROVED],
            [PLUGIN_DOCMAN_ITEM_STATUS_REJECTED],
        ];
    }

    /**
     * @dataProvider dataProviderDocmanStatus
     */
    public function testItReturnsNoneStatusIfTheExpectedStatusIDIsZero(): void
    {
        $metadata_list_of_values_element_factory = new Docman_MetadataListOfValuesElementFactory();

        $status = $metadata_list_of_values_element_factory->getStatusList(0);

        self::assertSame(PLUGIN_DOCMAN_ITEM_STATUS_NONE, $status->getId());
    }
}
