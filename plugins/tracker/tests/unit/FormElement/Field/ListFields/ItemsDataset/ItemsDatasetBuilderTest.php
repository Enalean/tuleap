<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ListFields\ItemsDataset;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_FormElement_Field_List_Value;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ItemsDatasetBuilderTest extends TestCase
{
    public function testItBuildsDataAttributesForFieldListValue(): void
    {
        $field            = ListFieldBuilder::aListField(452)->build();
        $field_list_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $field_list_value->method('getDataset')->willReturn([
            'data-user-id'    => 102,
            'data-avatar-url' => 'some_url',
            'data-color-name' => 'peggy-pink',
        ]);

        $data_attributes = ItemsDatasetBuilder::buildDataAttributesForValue($field, $field_list_value);
        self::assertSame(
            ' data-user-id="102" data-avatar-url="some_url" data-color-name="peggy-pink"',
            $data_attributes
        );
    }

    public function testItReturnsAnEmptyStringWhenThereIsNoDataToBind(): void
    {
        $field            = ListFieldBuilder::aListField(452)->build();
        $field_list_value = $this->createMock(Tracker_FormElement_Field_List_Value::class);
        $field_list_value->method('getDataset')->willReturn([]);

        $data_attributes = ItemsDatasetBuilder::buildDataAttributesForValue($field, $field_list_value);
        self::assertSame('', $data_attributes);
    }
}
