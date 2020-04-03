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

use Docman_Metadata;
use Docman_MetadataValueDao;
use Docman_MetadataValueList;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetadataValueUpdatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Docman_MetadataValueDao|Mockery\MockInterface
     */
    private $metadata_value_dao;
    /**
     * @var Mockery\MockInterface|MetadataValueStore
     */
    private $store;
    /**
     * @var MetadataValueCreator
     */
    private $updator;
    /**
     * @var \Mockery\MockInterface|MetadataValueObjectFactory
     */
    private $metadata_value_object_factory;

    /**
     * @var \Mockery\MockInterface|DocmanMetadataInputValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator                     = Mockery::mock(DocmanMetadataInputValidator::class);
        $this->metadata_value_object_factory = Mockery::mock(MetadataValueObjectFactory::class);
        $this->metadata_value_dao            = Mockery::mock(Docman_MetadataValueDao::class);
        $this->store                         = Mockery::mock(MetadataValueStore::class);

        $this->updator = new MetadataValueUpdator(
            $this->validator,
            $this->metadata_value_object_factory,
            $this->metadata_value_dao,
            $this->store
        );
    }

    public function testItCreateNewMetadataValueIfItemDoesNotHaveThisMetadataSet(): void
    {
        $metadata_value = \Mockery::mock(Docman_MetadataValueList::class);
        $metadata_value->shouldReceive('getFieldId')->andReturn(1);
        $metadata_value->shouldReceive('getItemId')->andReturn(100);

        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn(
            $metadata_value
        );

        $this->store->shouldReceive('storeMetadata')->withArgs([$metadata_value, 102])->once();
        $this->store->shouldReceive('updateMetadata')->never();

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);

        $this->metadata_value_dao->shouldReceive('searchById')->withArgs([1, 100])->andReturn(
            new \ArrayIterator()
        )->once();

        $this->validator->shouldReceive('validateInput')->withArgs([$metadata_to_create, 'new value'])->once();

        $this->updator->updateMetadata($metadata_to_create, 1000, 'new value');
    }

    public function testItUpdateExistingMetadataValue(): void
    {
        $metadata_value = \Mockery::mock(Docman_MetadataValueList::class);
        $metadata_value->shouldReceive('getFieldId')->andReturn(1);
        $metadata_value->shouldReceive('getItemId')->andReturn(100);

        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn(
            $metadata_value
        );

        $this->store->shouldReceive('storeMetadata')->never();
        $this->store->shouldReceive('updateMetadata')->withArgs([$metadata_value, 102])->once();

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);

        $this->metadata_value_dao->shouldReceive('searchById')->withArgs([1, 100])->andReturn(
            new \ArrayIterator(
                [
                    'field_id' => 1,
                    'item_id'  => 100,
                    'value'    => "old value"
                ]
            )
        )->once();

        $this->validator->shouldReceive('validateInput')->withArgs([$metadata_to_create, 'new value'])->once();

        $this->updator->updateMetadata($metadata_to_create, 1000, 'new value');
    }
}
