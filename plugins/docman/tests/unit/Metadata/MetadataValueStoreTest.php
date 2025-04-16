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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata;

use ArrayIterator;
use Docman_MetadataListOfValuesElement;
use Docman_MetadataValueDao;
use Docman_MetadataValueList;
use Docman_MetadataValueScalar;
use PHPUnit\Framework\MockObject\MockObject;
use ReferenceManager;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataValueStoreTest extends TestCase
{
    private MetadataValueStore $store;
    private ReferenceManager&MockObject $reference_manager;
    private Docman_MetadataValueDao&MockObject $metadata_value_dao;

    protected function setUp(): void
    {
        $this->metadata_value_dao = $this->createMock(Docman_MetadataValueDao::class);
        $this->reference_manager  = $this->createMock(ReferenceManager::class);

        $this->store = new MetadataValueStore(
            $this->metadata_value_dao,
            $this->reference_manager
        );
    }

    public function testItStoreListMetadata(): void
    {
        $list_value = new Docman_MetadataListOfValuesElement();
        $list_value->setId(42);

        $metadata_value = new Docman_MetadataValueList();
        $metadata_value->setValue(new ArrayIterator([$list_value]));
        $metadata_value->setItemId(1);
        $metadata_value->setFieldId(10);

        $this->metadata_value_dao->expects($this->once())->method('create');
        $this->reference_manager->expects($this->never())->method('extractCrossRef');
        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testItStoreTextMetadata(): void
    {
        $metadata_value = new Docman_MetadataValueScalar();
        $metadata_value->setItemId(1);
        $metadata_value->setFieldId(10);
        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_value->setValue('text value');

        $this->metadata_value_dao->expects($this->once())->method('create');
        $this->reference_manager->expects($this->once())->method('extractCrossRef');

        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testStoreThrowsExceptionIfMetadataIsNotFound(): void
    {
        $metadata_value = new Docman_MetadataValueScalar();
        $metadata_value->setType(1233);

        self::expectException(MetadataDoesNotExistException::class);
        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testItUpdateListMetadata(): void
    {
        $list_value = new Docman_MetadataListOfValuesElement();
        $list_value->setId(42);

        $metadata_value = new Docman_MetadataValueList();
        $metadata_value->setValue(new ArrayIterator([$list_value]));
        $metadata_value->setItemId(1);
        $metadata_value->setFieldId(10);

        $this->metadata_value_dao->expects($this->once())->method('delete')->with(10, 1);

        $this->metadata_value_dao->expects($this->once())->method('create');
        $this->reference_manager->expects($this->never())->method('extractCrossRef');
        $this->store->updateMetadata($metadata_value, 102);
    }

    public function testItUpdateTextMetadata(): void
    {
        $metadata_value = new Docman_MetadataValueScalar();
        $metadata_value->setItemId(1);
        $metadata_value->setFieldId(10);
        $metadata_value->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_value->setValue('text value');

        $this->metadata_value_dao->expects($this->once())->method('updateValue');
        $this->reference_manager->expects($this->once())->method('extractCrossRef');

        $this->store->updateMetadata($metadata_value, 102);
    }

    public function testUpdateThrowsExceptionIfMetadataIsNotFound(): void
    {
        $metadata_value = new Docman_MetadataValueScalar();
        $metadata_value->setType(1233);

        self::expectException(MetadataDoesNotExistException::class);
        $this->store->updateMetadata($metadata_value, 102);
    }
}
