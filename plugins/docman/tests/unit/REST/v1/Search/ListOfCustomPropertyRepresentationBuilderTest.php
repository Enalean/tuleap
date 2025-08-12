<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\Search;

use Tuleap\REST\JsonCast;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListOfCustomPropertyRepresentationBuilderTest extends TestCase
{
    public function testGetCustomProperties(): void
    {
        $metadata_string = new \Docman_Metadata();
        $metadata_string->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_string->setLabel('field_1');
        $metadata_string->setValue('Lorem ipsum');

        $metadata_text = new \Docman_Metadata();
        $metadata_text->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_text->setLabel('field_2');
        $metadata_text->setValue('doloret');

        $metadata_date = new \Docman_Metadata();
        $metadata_date->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $metadata_date->setLabel('field_3');
        $metadata_date->setValue(1234567890);

        $metadata_list = new \Docman_ListMetadata();
        $metadata_list->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $metadata_list->setLabel('field_4');
        $metadata_list->setValue(
            new \ArrayIterator(
                array_map(
                    static function (string $name): \Docman_MetadataListOfValuesElement {
                        $value = new \Docman_MetadataListOfValuesElement();
                        $value->setName($name);

                        return $value;
                    },
                    ['Am', 'Stram', 'Gram']
                )
            )
        );

        $item = new \Docman_Item();
        $item->addMetadata($metadata_string);
        $item->addMetadata($metadata_text);
        $item->addMetadata($metadata_date);
        $item->addMetadata($metadata_list);

        $columns = new SearchColumnCollection();
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_1', 'Comments'));
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_2', 'Comments2'));
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_3', 'Audit date'));
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_4', 'Choice'));

        $builder           = new ListOfCustomPropertyRepresentationBuilder();
        $custom_properties = $builder->getCustomProperties($item, $columns);

        self::assertEquals('string', $custom_properties['field_1']->type);
        self::assertEquals('Lorem ipsum', $custom_properties['field_1']->value);
        self::assertEquals('string', $custom_properties['field_2']->type);
        self::assertEquals('doloret', $custom_properties['field_2']->value);
        self::assertEquals('date', $custom_properties['field_3']->type);
        self::assertEquals(JsonCast::toDate(1234567890), $custom_properties['field_3']->value);
        self::assertEquals('list', $custom_properties['field_4']->type);
        self::assertEquals(['Am', 'Stram', 'Gram'], $custom_properties['field_4']->values);
    }

    public function testWhenMetadataStringIsNullThenWeOutputEmptyString(): void
    {
        $metadata_string = new \Docman_Metadata();
        $metadata_string->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);
        $metadata_string->setLabel('field_1');
        $metadata_string->setValue(null);

        $metadata_text = new \Docman_Metadata();
        $metadata_text->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $metadata_text->setLabel('field_2');
        $metadata_text->setValue(null);

        $item = new \Docman_Item();
        $item->addMetadata($metadata_string);
        $item->addMetadata($metadata_text);

        $columns = new SearchColumnCollection();
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_1', 'Comments'));
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_2', 'Comments2'));

        $builder           = new ListOfCustomPropertyRepresentationBuilder();
        $custom_properties = $builder->getCustomProperties($item, $columns);

        self::assertEquals('string', $custom_properties['field_1']->type);
        self::assertEquals('', $custom_properties['field_1']->value);
        self::assertEquals('string', $custom_properties['field_2']->type);
        self::assertEquals('', $custom_properties['field_2']->value);
    }

    #[\PHPUnit\Framework\Attributes\TestWith([null])]
    #[\PHPUnit\Framework\Attributes\TestWith([0])]
    public function testDateIsNullWhenNotSet(?int $timestamp): void
    {
        $metadata_date = new \Docman_Metadata();
        $metadata_date->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $metadata_date->setLabel('field_3');
        $metadata_date->setValue($timestamp);

        $item = new \Docman_Item();
        $item->addMetadata($metadata_date);

        $columns = new SearchColumnCollection();
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_3', 'Audit date'));

        $builder           = new ListOfCustomPropertyRepresentationBuilder();
        $custom_properties = $builder->getCustomProperties($item, $columns);

        self::assertEquals('date', $custom_properties['field_3']->type);
        self::assertNull($custom_properties['field_3']->value);
    }

    public function testItDoesNotAddCustomPropertiesIfNoMatchingMetadata(): void
    {
        $metadata_date = new \Docman_Metadata();
        $metadata_date->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);
        $metadata_date->setLabel('field_3');
        $metadata_date->setValue(1234567890);

        $item = new \Docman_Item();
        $item->addMetadata($metadata_date);

        $columns = new SearchColumnCollection();
        $columns->add(SearchColumn::buildForSingleValueCustomProperty('field_4', 'Choice'));

        $builder           = new ListOfCustomPropertyRepresentationBuilder();
        $custom_properties = $builder->getCustomProperties($item, $columns);

        self::assertCount(0, $custom_properties);
    }
}
