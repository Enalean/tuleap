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
use Docman_MetadataValueDao;
use Docman_MetadataValueList;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReferenceManager;

class MetadataValueStoreTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|MetadataValueStore
     */
    private $store;
    /**
     * @var \Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Docman_MetadataValueDao|\Mockery\MockInterface
     */
    private $metadata_value_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_value_dao = Mockery::mock(Docman_MetadataValueDao::class);
        $this->reference_manager  = Mockery::mock(ReferenceManager::class);

        $this->store = new MetadataValueStore(
            $this->metadata_value_dao,
            $this->reference_manager
        );
    }

    public function testItStoreListMetadata(): void
    {
        $list_value = \Mockery::mock(\Docman_MetadataListOfValuesElement::class);
        $list_value->shouldReceive('getId')->andReturn(42);

        $metadata_value = \Mockery::mock(Docman_MetadataValueList::class);
        $metadata_value->shouldReceive('getValue')->andReturn(
            new ArrayIterator([$list_value])
        );
        $metadata_value->shouldReceive('getItemId')->andReturn(1);
        $metadata_value->shouldReceive('getFieldId')->andReturn(10);
        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $this->metadata_value_dao->shouldReceive('create')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->never();
        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testItStoreTextMetadata(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $metadata_value->shouldReceive('getItemId')->andReturn(1);
        $metadata_value->shouldReceive('getFieldId')->andReturn(10);
        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_value->shouldReceive('getValue')->andReturn('text value');

        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->metadata_value_dao->shouldReceive('create')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->once();

        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testStoreThrowsExceptionIfMetadataIsNotFound(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $metadata_value->shouldReceive('getType')->andReturn(1233);

        $this->expectException(MetadataDoesNotExistException::class);
        $this->store->storeMetadata($metadata_value, 102);
    }

    public function testItUpdateListMetadata(): void
    {
        $list_value = \Mockery::mock(\Docman_MetadataListOfValuesElement::class);
        $list_value->shouldReceive('getId')->andReturn(42);

        $metadata_value = \Mockery::mock(Docman_MetadataValueList::class);
        $metadata_value->shouldReceive('getValue')->andReturn(
            new ArrayIterator([$list_value])
        );
        $metadata_value->shouldReceive('getItemId')->andReturn(1);
        $metadata_value->shouldReceive('getFieldId')->andReturn(10);
        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $this->metadata_value_dao->shouldReceive('delete')->withArgs([10, 1])->once();

        $this->metadata_value_dao->shouldReceive('create')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->never();
        $this->store->updateMetadata($metadata_value, 102);
    }

    public function testItUpdateTextMetadata(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $metadata_value->shouldReceive('getItemId')->andReturn(1);
        $metadata_value->shouldReceive('getFieldId')->andReturn(10);
        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_value->shouldReceive('getValue')->andReturn('text value');

        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->metadata_value_dao->shouldReceive('updateValue')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->once();

        $this->store->updateMetadata($metadata_value, 102);
    }

    public function testUpdateThrowsExceptionIfMetadataIsNotFound(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $metadata_value->shouldReceive('getType')->andReturn(1233);

        $this->expectException(MetadataDoesNotExistException::class);
        $this->store->updateMetadata($metadata_value, 102);
    }
}
