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

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_MetadataListOfValuesElement;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;

class CustomMetadataCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_MetadataFactory|\Mockery\MockInterface
     */
    private $metadata_factory;
    /**
     * @var MetadataListOfValuesElementListBuilder|\Mockery\MockInterface
     */
    private $list_of_value_builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metadata_factory = \Mockery::mock(\Docman_MetadataFactory::class);
        $this->list_of_value_builder = \Mockery::mock(MetadataListOfValuesElementListBuilder::class);
    }

    public function testBuildCollectionOfMetadata(): void
    {
        $builder = new CustomMetadataCollectionBuilder($this->metadata_factory, $this->list_of_value_builder);

        $list_metadata = \Mockery::mock(\Docman_ListMetadata::class);
        $metadata      = \Mockery::mock(\Docman_Metadata::class);

        $list_metadata->shouldReceive('getId')->andReturn(1);
        $list_metadata->shouldReceive('getLabel')->andReturn("label list");
        $metadata->shouldReceive('getLabel')->andReturn("label");
        $list_metadata->shouldReceive("getName")->andReturn("name list");
        $metadata->shouldReceive("getName")->andReturn("name");
        $list_metadata->shouldReceive("getDescription")->andReturn("");
        $metadata->shouldReceive("getDescription")->andReturn("");
        $list_metadata->shouldReceive("getType")->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata->shouldReceive("getType")->andReturn(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $list_metadata->shouldReceive("isEmptyAllowed")->andReturn(false);
        $metadata->shouldReceive("isEmptyAllowed")->andReturn(false);
        $list_metadata->shouldReceive("isMultipleValuesAllowed")->andReturn(false);
        $metadata->shouldReceive("isMultipleValuesAllowed")->andReturn(false);
        $list_metadata->shouldReceive("isUsed")->andReturn(true);
        $metadata->shouldReceive("isUsed")->andReturn(true);

        $metadata_list = [$list_metadata, $metadata];
        $this->metadata_factory->shouldReceive("getRealMetadataList")->andReturn($metadata_list);

        $element = new Docman_MetadataListOfValuesElement();
        $element->initFromRow(['value_id' => 1, 'name' => "value"]);

        $element_two = new Docman_MetadataListOfValuesElement();
        $element_two->initFromRow(['value_id' => 2, 'name' => "an other value"]);

        $value_representation = new DocmanMetadataListValueRepresentation();
        $value_representation->build($element->getId(), $element->getName());

        $value_two_representation = new DocmanMetadataListValueRepresentation();
        $value_two_representation->build($element_two->getId(), $element_two->getName());

        $metadata_list_representation = new ProjectConfiguredMetadataRepresentation();
        $metadata_list_representation->build(
            $list_metadata->getLabel(),
            $list_metadata->getName(),
            $list_metadata->getDescription(),
            $list_metadata->getType(),
            $list_metadata->isEmptyAllowed(),
            $list_metadata->isMultipleValuesAllowed(),
            $list_metadata->isUsed(),
            [$value_representation, $value_two_representation]
        );

        $this->list_of_value_builder->shouldReceive('build')->withArgs([$list_metadata->getId(), false])->andReturn([$element, $element_two]);

        $metadata_representation = new ProjectConfiguredMetadataRepresentation();
        $metadata_representation->build(
            $metadata->getLabel(),
            $metadata->getName(),
            $metadata->getDescription(),
            $metadata->getType(),
            $metadata->isEmptyAllowed(),
            $metadata->isMultipleValuesAllowed(),
            $metadata->isUsed(),
            null
        );

        $expected_content = CustomMetadataCollection::build([$metadata_list_representation, $metadata_representation]);

        $custom_metadata_representation = $builder->build();

        $this->assertEquals($expected_content, $custom_metadata_representation);
    }
}
