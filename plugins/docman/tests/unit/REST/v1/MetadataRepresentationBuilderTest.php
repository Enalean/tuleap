<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Codendi_HTMLPurifier;
use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Metadata\MetadataListValueRepresentation;
use Tuleap\Docman\REST\v1\Metadata\ItemMetadataRepresentation;
use Tuleap\Docman\REST\v1\Metadata\MetadataRepresentationBuilder;
use UserHelper;

class MetadataRepresentationBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildMetadataWithoutBasicProperties(): void
    {
        $item = Mockery::mock(\Docman_Item::class);

        $factory       = Mockery::mock(Docman_MetadataFactory::class);
        $html_purifier = Mockery::mock(Codendi_HTMLPurifier::class);
        $builder       = new MetadataRepresentationBuilder($factory, $html_purifier, Mockery::mock(UserHelper::class));

        $simple_metadata = Mockery::mock(Docman_Metadata::class);
        $simple_metadata->shouldReceive('getValue')->andReturn("my simple value");
        $simple_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $simple_metadata->shouldReceive('getName')->andReturn("simple metadata label");
        $simple_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $simple_metadata->shouldReceive('isEmptyAllowed')->andReturn(true);
        $simple_metadata->shouldReceive('getLabel')->andReturn("simple_metadata_label");
        $simple_metadata->shouldReceive('getGroupId')->andReturn(102);

        $value1 = Mockery::mock(\Docman_MetadataListOfValuesElement::class);
        $value1->shouldReceive('getId')->andReturn(1);
        $value1->shouldReceive('getMetadataValue')->andReturn("My value 1");
        $value2 = Mockery::mock(\Docman_MetadataListOfValuesElement::class);
        $value2->shouldReceive('getId')->andReturn(100);
        $value2->shouldReceive('getMetadataValue')->andReturn("None");
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
        $list_metadata->shouldReceive('isEmptyAllowed')->andReturn(false);
        $list_metadata->shouldReceive('getLabel')->andReturn("list_metadata_label");

        $factory->shouldReceive('appendItemMetadataList');
        $item->shouldReceive('getMetadata')->andReturn(
            [
                $simple_metadata,
                $list_metadata
            ]
        );
        $html_purifier->shouldReceive('purifyTextWithReferences')->andReturn('value with references');

        $representation = $builder->build($item);

        $expected_representation = [
            new ItemMetadataRepresentation(
                "simple metadata label",
                'text',
                false,
                "my simple value",
                'value with references',
                null,
                true,
                "simple_metadata_label"
            ),
            new ItemMetadataRepresentation(
                "list metadata label",
                'list',
                true,
                null,
                null,
                [
                    new MetadataListValueRepresentation(1, "My value 1"),
                    new MetadataListValueRepresentation(100, "None")
                ],
                false,
                "list_metadata_label"
            ),
        ];

        $this->assertEquals($representation, $expected_representation);
    }

    public function testMetadataWithDatePropertyIsCorrectlyBuilt(): void
    {
        $item = Mockery::mock(\Docman_Item::class);

        $factory = Mockery::mock(Docman_MetadataFactory::class);
        $builder = new MetadataRepresentationBuilder(
            $factory,
            Mockery::mock(Codendi_HTMLPurifier::class),
            Mockery::mock(UserHelper::class)
        );

        $date_metadata = Mockery::mock(Docman_Metadata::class);
        $date_metadata->shouldReceive('getValue')->andReturn('1');
        $date_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $date_metadata->shouldReceive('getName')->andReturn('date metadata name');
        $date_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $date_metadata->shouldReceive('isEmptyAllowed')->andReturn(true);
        $date_metadata->shouldReceive('getLabel')->andReturn('date metadata label');

        $factory->shouldReceive('appendItemMetadataList');

        $item->shouldReceive('getMetadata')->andReturn([$date_metadata]);

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'date metadata name',
            'date',
            false,
            '1970-01-01T01:00:01+01:00',
            '1970-01-01T01:00:01+01:00',
            null,
            true,
            'date metadata label'
        );

        $this->assertEquals([$expected_representation], $representation);
    }

    /**
     * @testWith [0]
     *           ["0"]
     */
    public function testMetadataWithDatePropertyButWithoutActualValueIsCorrectlyBuilt($value): void
    {
        $item = Mockery::mock(\Docman_Item::class);

        $factory = Mockery::mock(Docman_MetadataFactory::class);
        $builder = new MetadataRepresentationBuilder(
            $factory,
            Mockery::mock(Codendi_HTMLPurifier::class),
            Mockery::mock(UserHelper::class)
        );

        $date_metadata = Mockery::mock(Docman_Metadata::class);
        $date_metadata->shouldReceive('getValue')->andReturn($value);
        $date_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $date_metadata->shouldReceive('getName')->andReturn('date metadata name');
        $date_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $date_metadata->shouldReceive('isEmptyAllowed')->andReturn(true);
        $date_metadata->shouldReceive('getLabel')->andReturn('date metadata label');

        $factory->shouldReceive('appendItemMetadataList');

        $item->shouldReceive('getMetadata')->andReturn([$date_metadata]);

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'date metadata name',
            'date',
            false,
            null,
            null,
            null,
            true,
            'date metadata label'
        );

        $this->assertEquals([$expected_representation], $representation);
    }

    public function testMetadataOwnerPropertyIsCorrectlyBuilt(): void
    {
        $item = Mockery::mock(\Docman_Item::class);

        $factory     = Mockery::mock(Docman_MetadataFactory::class);
        $user_helper = Mockery::mock(UserHelper::class);
        $builder     = new MetadataRepresentationBuilder(
            $factory,
            Mockery::mock(Codendi_HTMLPurifier::class),
            $user_helper
        );

        $owner_metadata = Mockery::mock(Docman_Metadata::class);
        $owner_metadata->shouldReceive('getValue')->andReturn('1');
        $owner_metadata->shouldReceive('isMultipleValuesAllowed')->andReturn(false);
        $owner_metadata->shouldReceive('getName')->andReturn('owner');
        $owner_metadata->shouldReceive('getType')->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $owner_metadata->shouldReceive('isEmptyAllowed')->andReturn(true);
        $owner_metadata->shouldReceive('getLabel')->andReturn('owner');

        $factory->shouldReceive('appendItemMetadataList');

        $item->shouldReceive('getMetadata')->andReturn([$owner_metadata]);

        $user_helper->shouldReceive('getDisplayNameFromUserId')->andReturn('user display name');
        $user_helper->shouldReceive('getLinkOnUserFromUserId')->andReturn('user display name with link');

        $representation          = $builder->build($item);
        $expected_representation = new ItemMetadataRepresentation(
            'owner',
            'string',
            false,
            'user display name',
            'user display name with link',
            null,
            true,
            'owner'
        );

        $this->assertEquals([$expected_representation], $representation);
    }
}
