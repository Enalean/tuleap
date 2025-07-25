<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\FieldSpecificProperties;

use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldPropertiesRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItRetrievesSpecificPropertiesInCache(): void
    {
        $dao                        = $this->createMock(SearchSpecificProperties::class);
        $field_properties_retriever = new FieldPropertiesRetriever($dao);

        $cache              = ['default_value' => '22'];
        $default_properties = [];
        $field_id           = 1;

        $result = $field_properties_retriever->getProperties($cache, $default_properties, $field_id);

        $this->assertSame($cache, $result);
    }

    public function testItReturnsDefaultValuesWhenFieldHasNoSpecificDao(): void
    {
        $field_properties_retriever = new FieldPropertiesRetriever(null);

        $cache              = null;
        $default_properties = ['default_value' => 'value'];
        $field_id           = 1;

        $result = $field_properties_retriever->getProperties($cache, $default_properties, $field_id);

        $this->assertSame($default_properties, $result);
    }

    public function testItReturnsDefaultValuesWhenSpecificPropertiesAreNotStoredInDb(): void
    {
        $dao = $this->createMock(SearchSpecificProperties::class);

        $dao->expects($this->once())
            ->method('searchByFieldId')
            ->willReturn(null);

        $field_properties_retriever = new FieldPropertiesRetriever($dao);

        $cache              = null;
        $default_properties = ['default_value' => 'value'];
        $field_id           = 1;

        $result = $field_properties_retriever->getProperties($cache, $default_properties, $field_id);

        $this->assertSame($default_properties, $result);
    }

    public function testGetPropertiesSetsChoicesDefaultValueForAnIntField(): void
    {
        $maxchar_value = 10;
        $size_value    = 20;
        $default_value = 30;
        $dao           = $this->createMock(SearchSpecificProperties::class);

        $dao->expects($this->once())
            ->method('searchByFieldId')
            ->willReturn([
                'field_id' => 10118,
                'default_value' => $default_value,
                'maxchars' => $maxchar_value,
                'size' => $size_value,
            ]);

        $field_properties_retriever = new FieldPropertiesRetriever($dao);

        $cache              = null;
        $field              = IntegerFieldBuilder::anIntField(1)->build();
        $default_properties =   $field->default_properties;

        $result = $field_properties_retriever->getProperties($cache, $default_properties, $field->id);

        $this->assertSame($maxchar_value, $result['maxchars']['value']);
        $this->assertSame($size_value, $result['size']['value']);
        $this->assertSame($default_value, $result['default_value']['value']);
    }

    public function testGetPropertiesSetsChoicesDefaultValueForDateTimeField(): void
    {
        $dao = $this->createMock(SearchSpecificProperties::class);

        $default_date = 1704150000;
        $dao->expects($this->once())
            ->method('searchByFieldId')
            ->willReturn([
                'field_id' => 10116,
                'default_value' => $default_date,
                'default_value_type' => 1,
                'display_time' => 1,
            ]);

        $field_properties_retriever = new FieldPropertiesRetriever($dao);

        $cache              = null;
        $field              = DateFieldBuilder::aDateField(1)->build();
        $default_properties =   $field->default_properties;

        $result = $field_properties_retriever->getProperties($cache, $default_properties, $field->id);

        $this->assertSame($default_date, $result['default_value_type']['choices']['default_value']['value']);
    }
}
