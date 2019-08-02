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

declare(strict_types = 1);

namespace Tuleap\Docman\Metadata;

use ArrayIterator;
use Docman_Metadata;
use Docman_MetadataValueDao;
use Docman_MetadataValueList;
use LogicException;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReferenceManager;

class MetadataValueCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var MetadataValueCreator
     */
    private $creator;
    /**
     * @var \Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var Docman_MetadataValueDao|\Mockery\MockInterface
     */
    private $metadata_value_dao;
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
        $this->reference_manager             = Mockery::mock(ReferenceManager::class);

        $this->creator = new MetadataValueCreator(
            $this->validator,
            $this->metadata_value_object_factory,
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

        $this->validator->shouldReceive('validateInput');
        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn($metadata_value);

        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $this->metadata_value_dao->shouldReceive('create')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->never();

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);
        $this->creator->createMetadataObject($metadata_to_create, 1000, 'new value');
    }

    public function testItStoreTextMetadata(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $metadata_value->shouldReceive('getItemId')->andReturn(1);
        $metadata_value->shouldReceive('getFieldId')->andReturn(10);
        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_value->shouldReceive('getValue')->andReturn('text value');

        $this->validator->shouldReceive('validateInput');
        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn($metadata_value);

        $metadata_value->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $this->metadata_value_dao->shouldReceive('create')->once();
        $this->reference_manager->shouldReceive('extractCrossRef')->once();

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);
        $this->creator->createMetadataObject($metadata_to_create, 1000, 'new value');
    }

    public function testItThrowsExceptionIfMetadataIsNotFound(): void
    {
        $metadata_value = \Mockery::mock(\Docman_MetadataValueScalar::class);
        $this->validator->shouldReceive('validateInput');
        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn($metadata_value);

        $metadata_value->shouldReceive('getType')->andReturn(1233);

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $this->expectException(MetadataDoesNotExistException::class);
        $this->creator->createMetadataObject($metadata_to_create, 1000, 'new value');
    }
}
