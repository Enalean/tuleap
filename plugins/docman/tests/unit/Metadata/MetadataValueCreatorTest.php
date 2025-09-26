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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataValueCreatorTest extends TestCase
{
    private MetadataValueStore&MockObject $store;
    private MetadataValueCreator $creator;
    private MetadataValueObjectFactory&MockObject $metadata_value_object_factory;
    private DocmanMetadataInputValidator&MockObject $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator                     = $this->createMock(DocmanMetadataInputValidator::class);
        $this->metadata_value_object_factory = $this->createMock(MetadataValueObjectFactory::class);
        $this->store                         = $this->createMock(MetadataValueStore::class);

        $this->creator = new MetadataValueCreator(
            $this->validator,
            $this->metadata_value_object_factory,
            $this->store
        );
    }

    public function testItValidateAndCreateAMetadataObject(): void
    {
        $metadata_value = $this->createMock(Docman_MetadataValueList::class);

        $this->metadata_value_object_factory->method('createMetadataValueObjectWithCorrectValue')->willReturn($metadata_value);

        $this->store->expects($this->once())->method('storeMetadata')->with($metadata_value, 102);

        $metadata_to_create = new Docman_Metadata();
        $metadata_to_create->initFromRow(['id' => 1, 'type' => PLUGIN_DOCMAN_METADATA_TYPE_LIST, 'group_id' => 102]);

        $this->validator->expects($this->once())->method('validateInput')->with($metadata_to_create, 'new value');

        $this->creator->createMetadataObject($metadata_to_create, 1000, 'new value');
    }
}
