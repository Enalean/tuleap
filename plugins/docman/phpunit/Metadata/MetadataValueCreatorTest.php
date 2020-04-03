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
use Docman_MetadataValueList;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetadataValueCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|MetadataValueStore
     */
    private $store;
    /**
     * @var MetadataValueCreator
     */
    private $creator;
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
        $this->store                         = Mockery::mock(MetadataValueStore::class);

        $this->creator = new MetadataValueCreator(
            $this->validator,
            $this->metadata_value_object_factory,
            $this->store
        );
    }

    public function testItValidateAndCreateAMetadataObject(): void
    {
        $metadata_value = \Mockery::mock(Docman_MetadataValueList::class);

        $this->metadata_value_object_factory->shouldReceive('createMetadataValueObjectWithCorrectValue')->andReturn($metadata_value);

        $this->store->shouldReceive('storeMetadata')->withArgs([$metadata_value, 102])->once();

        $metadata_to_create = Mockery::mock(Docman_Metadata::class);
        $metadata_to_create->shouldReceive('getId')->andReturn(1);
        $metadata_to_create->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_to_create->shouldReceive('getGroupId')->andReturn(102);

        $this->validator->shouldReceive('validateInput')->withArgs([$metadata_to_create, 'new value'])->once();

        $this->creator->createMetadataObject($metadata_to_create, 1000, 'new value');
    }
}
