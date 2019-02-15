<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1;

use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetadataRepresentationBuilderTest extends TestCase
{
    public function testItBuildMetadataWithoutBasicProperties()
    {
        $item = Mockery::mock(\Docman_Item::class);

        $factory = mockery::mock(Docman_MetadataFactory::class);
        $builder = new MetadataRepresentationBuilder($factory);

        $simple_metadata = Mockery::mock(Docman_Metadata::class);
        $simple_metadata->shouldReceive('getValue')->andReturn("my simple value");
        $simple_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $simple_metadata->shouldReceive('getName')->andReturn("simple metadata label");
        $simple_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $simple_metadata->shouldReceive('isRequired')->andReturn(false);
        $simple_metadata->shouldReceive('getLabel')->andReturn("simple_metadata_label");

        $value1 = Mockery::mock(\Docman_MetadataValueList::class);
        $value1->shouldReceive('getId')->andReturn(1);
        $value1->shouldReceive('getName')->andReturn("My value 1");
        $value2 = Mockery::mock(\Docman_MetadataValueList::class);
        $value2->shouldReceive('getId')->andReturn(2);
        $value2->shouldReceive('getName')->andReturn("My value 2");
        $list_metadata   = Mockery::mock(Docman_ListMetadata::class);
        $list_metadata->shouldReceive('getValue')->andReturn(
            new \ArrayIterator([
                $value1,
                $value2
            ])
        );
        $list_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(true);
        $list_metadata->shouldReceive('getName')->andReturn("list metadata label");
        $list_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $list_metadata->shouldReceive('isRequired')->andReturn(true);
        $list_metadata->shouldReceive('getLabel')->andReturn("list_metadata_label");

        $factory->shouldReceive('appendItemMetadataListWithoutBasicProperties');
        $item->shouldReceive('getMetadata')->andReturn(
            [
                $simple_metadata,
                $list_metadata
            ]
        );

        $representation = $builder->build($item);

        $expected_representation = [
            new MetadataRepresentation(
                "simple metadata label",
                'text',
                false,
                "my simple value",
                null,
                false
            ),
            new MetadataRepresentation(
                "list metadata label",
                'list',
                true,
                "",
                [
                    new MetadataListValueRepresentation(1, "My value 1"),
                    new MetadataListValueRepresentation(2, "My value 2")
                ],
                true
            ),
        ];

        $this->assertEquals($representation, $expected_representation);
    }
}
