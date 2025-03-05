<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_Column;
use Cardwall_OnTop_Config_TrackerMappingStatus;
use Cardwall_OnTop_Config_ValueMapping;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_TrackerMappingStatusTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var array<int, Cardwall_OnTop_Config_ValueMapping[]>
     */
    private array $value_mappings;

    protected function setUp(): void
    {
        $value_none       = new Tracker_FormElement_Field_List_Bind_StaticValue(100, 'None', '', 0, 0);
        $value_todo       = new Tracker_FormElement_Field_List_Bind_StaticValue(101, 'Todo', '', 0, 0);
        $value_inprogress = new Tracker_FormElement_Field_List_Bind_StaticValue(102, 'In Progress', '', 0, 0);
        $value_done       = new Tracker_FormElement_Field_List_Bind_StaticValue(103, 'Done', '', 0, 0);

        $mapping_none    = new Cardwall_OnTop_Config_ValueMapping($value_none, 10);
        $mapping_todo    = new Cardwall_OnTop_Config_ValueMapping($value_todo, 10);
        $mapping_ongoing = new Cardwall_OnTop_Config_ValueMapping($value_inprogress, 11);
        $mapping_done    = new Cardwall_OnTop_Config_ValueMapping($value_done, 12);

        $this->value_mappings = [
            100 => $mapping_none,
            101 => $mapping_todo,
            102 => $mapping_ongoing,
            103 => $mapping_done,
        ];
    }

    public function testItReturnsAnEmptyLabelWhenThereIsNoValueMapping(): void
    {
        $value_mappings = [];
        $mapping        = new Cardwall_OnTop_Config_TrackerMappingStatus(TrackerTestBuilder::aTracker()->build(), [], $value_mappings, ListFieldBuilder::aListField(875)->build());
        $column         = new Cardwall_Column(0, 'whatever', 'white');
        self::assertEquals('', $mapping->getSelectedValueLabel($column));
    }

    public function testItReturnsAnEmptyLabelWhenThereIsNoMappingForTheGivenColumn(): void
    {
        $mapping                 = new Cardwall_OnTop_Config_TrackerMappingStatus(TrackerTestBuilder::aTracker()->build(), [], $this->value_mappings, ListFieldBuilder::aListField(875)->build());
        $column_which_match      = new Cardwall_Column(11, 'Ongoing', 'white');
        $column_which_dont_match = new Cardwall_Column(13, 'Ship It', 'white');
        self::assertEquals('In Progress', $mapping->getSelectedValueLabel($column_which_match));
        self::assertEquals('', $mapping->getSelectedValueLabel($column_which_dont_match));
        self::assertEquals('Accept a default value', $mapping->getSelectedValueLabel($column_which_dont_match, 'Accept a default value'));
    }

    public function testItIsMappedToAColumnWhenTheStatusValueMatchColumnMapping(): void
    {
        $mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(TrackerTestBuilder::aTracker()->build(), [], $this->value_mappings, ListFieldBuilder::aListField(875)->build());

        $column = new Cardwall_Column(11, 'Ongoing', '');

        self::assertTrue($mapping->isMappedTo($column, 'In Progress'));
        self::assertFalse($mapping->isMappedTo($column, 'Ongoing'));
        self::assertFalse($mapping->isMappedTo($column, 'Todo'));
        self::assertFalse($mapping->isMappedTo($column, null));
    }

    public function testItIsMappedToAColumnWhenStatusIsNullAndNoneIsMappedToColumn(): void
    {
        $mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(TrackerTestBuilder::aTracker()->build(), [], $this->value_mappings, ListFieldBuilder::aListField(875)->build());

        $column = new Cardwall_Column(10, 'Todo', '');

        self::assertTrue($mapping->isMappedTo($column, null));

        self::assertFalse($mapping->isMappedTo($column, 'In Progress'));
    }

    public function testItDoesntMapOnNoneIfItsNotExplicitlyConfigured(): void
    {
        $mapping = new Cardwall_OnTop_Config_TrackerMappingStatus(TrackerTestBuilder::aTracker()->build(), [], $this->value_mappings, ListFieldBuilder::aListField(875)->build());

        $column = new Cardwall_Column(100, 'None', '');

        self::assertFalse($mapping->isMappedTo($column, null));
    }
}
