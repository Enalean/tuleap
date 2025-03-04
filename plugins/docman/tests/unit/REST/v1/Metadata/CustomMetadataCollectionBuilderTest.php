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

use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_MetadataListOfValuesElement;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Metadata\ListOfValuesElement\MetadataListOfValuesElementListBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CustomMetadataCollectionBuilderTest extends TestCase
{
    private Docman_MetadataFactory&MockObject $metadata_factory;
    private MetadataListOfValuesElementListBuilder&MockObject $list_of_value_builder;

    protected function setUp(): void
    {
        $this->metadata_factory      = $this->createMock(Docman_MetadataFactory::class);
        $this->list_of_value_builder = $this->createMock(MetadataListOfValuesElementListBuilder::class);
    }

    public function testBuildCollectionOfMetadata(): void
    {
        $builder = new CustomMetadataCollectionBuilder($this->metadata_factory, $this->list_of_value_builder);

        $list_metadata = new Docman_ListMetadata();
        $list_metadata->setId(1);
        $list_metadata->setLabel('label list');
        $list_metadata->setName('name list');
        $list_metadata->setDescription('');
        $list_metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $list_metadata->setIsEmptyAllowed(false);
        $list_metadata->setIsMultipleValuesAllowed(false);
        $list_metadata->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $metadata = new Docman_Metadata();
        $metadata->setLabel('label');
        $metadata->setName('name');
        $metadata->setDescription('');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata->setIsEmptyAllowed(false);
        $metadata->setIsMultipleValuesAllowed(false);
        $metadata->setUseIt(PLUGIN_DOCMAN_METADATA_USED);

        $this->metadata_factory->method('getRealMetadataList')->willReturn([$list_metadata, $metadata]);

        $element = new Docman_MetadataListOfValuesElement();
        $element->initFromRow(['value_id' => 1, 'name' => 'value']);

        $element_two = new Docman_MetadataListOfValuesElement();
        $element_two->initFromRow(['value_id' => 2, 'name' => 'an other value']);

        $value_representation = new DocmanMetadataListValueRepresentation();
        $value_representation->build((int) $element->getId(), (string) $element->getName());

        $value_two_representation = new DocmanMetadataListValueRepresentation();
        $value_two_representation->build((int) $element_two->getId(), (string) $element_two->getName());

        $metadata_list_representation = new ProjectConfiguredMetadataRepresentation();
        $metadata_list_representation->build(
            (string) $list_metadata->getLabel(),
            (string) $list_metadata->getName(),
            $list_metadata->getDescription(),
            (int) $list_metadata->getType(),
            $list_metadata->isEmptyAllowed(),
            $list_metadata->isMultipleValuesAllowed(),
            $list_metadata->isUsed(),
            [$value_representation, $value_two_representation]
        );

        $this->list_of_value_builder->method('build')->with($list_metadata->getId(), true)->willReturn([$element, $element_two]);

        $metadata_representation = new ProjectConfiguredMetadataRepresentation();
        $metadata_representation->build(
            (string) $metadata->getLabel(),
            (string) $metadata->getName(),
            $metadata->getDescription(),
            (int) $metadata->getType(),
            $metadata->isEmptyAllowed(),
            $metadata->isMultipleValuesAllowed(),
            $metadata->isUsed(),
            null
        );

        $expected_content = CustomMetadataCollection::build([$metadata_list_representation, $metadata_representation]);

        $custom_metadata_representation = $builder->build();

        self::assertEquals($expected_content, $custom_metadata_representation);
    }
}
