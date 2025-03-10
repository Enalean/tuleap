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
use Docman_Metadata;
use Docman_MetadataValueDao;
use Docman_MetadataValueList;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataValueUpdatorTest extends TestCase
{
    private Docman_MetadataValueDao&MockObject $metadata_value_dao;
    private MetadataValueStore&MockObject $store;
    private MetadataValueCreator|MetadataValueUpdator $updator;
    private MetadataValueObjectFactory&MockObject $metadata_value_object_factory;
    private DocmanMetadataInputValidator&MockObject $validator;

    protected function setUp(): void
    {
        $this->validator                     = $this->createMock(DocmanMetadataInputValidator::class);
        $this->metadata_value_object_factory = $this->createMock(MetadataValueObjectFactory::class);
        $this->metadata_value_dao            = $this->createMock(Docman_MetadataValueDao::class);
        $this->store                         = $this->createMock(MetadataValueStore::class);

        $this->updator = new MetadataValueUpdator(
            $this->validator,
            $this->metadata_value_object_factory,
            $this->metadata_value_dao,
            $this->store
        );
    }

    public function testItCreateNewMetadataValueIfItemDoesNotHaveThisMetadataSet(): void
    {
        $metadata_value = new Docman_MetadataValueList();
        $metadata_value->setFieldId(1);
        $metadata_value->setItemId(100);

        $this->metadata_value_object_factory->method('createMetadataValueObjectWithCorrectValue')->willReturn($metadata_value);

        $this->store->expects(self::once())->method('storeMetadata')->with($metadata_value, 102);
        $this->store->expects(self::never())->method('updateMetadata');

        $metadata_to_create = new Docman_Metadata();
        $metadata_to_create->setId(1);
        $metadata_to_create->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->setGroupId(102);

        $this->metadata_value_dao->expects(self::once())->method('searchById')->with(1, 100)->willReturn(new ArrayIterator());

        $this->validator->expects(self::once())->method('validateInput')->with($metadata_to_create, 'new value');

        $this->updator->updateMetadata($metadata_to_create, 1000, 'new value');
    }

    public function testItUpdateExistingMetadataValue(): void
    {
        $metadata_value = new Docman_MetadataValueList();
        $metadata_value->setFieldId(1);
        $metadata_value->setItemId(100);

        $this->metadata_value_object_factory->method('createMetadataValueObjectWithCorrectValue')->willReturn($metadata_value);

        $this->store->expects(self::never())->method('storeMetadata');
        $this->store->expects(self::once())->method('updateMetadata')->with($metadata_value, 102);

        $metadata_to_create = new Docman_Metadata();
        $metadata_to_create->setId(1);
        $metadata_to_create->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->setGroupId(102);

        $this->metadata_value_dao->expects(self::once())->method('searchById')->with(1, 100)->willReturn(new ArrayIterator([
            'field_id' => 1,
            'item_id'  => 100,
            'value'    => 'old value',
        ]));

        $this->validator->expects(self::once())->method('validateInput')->with($metadata_to_create, 'new value');

        $this->updator->updateMetadata($metadata_to_create, 1000, 'new value');
    }
}
