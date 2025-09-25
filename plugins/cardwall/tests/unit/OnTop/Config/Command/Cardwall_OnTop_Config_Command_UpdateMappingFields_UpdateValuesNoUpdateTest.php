<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config\Command;

use Cardwall_OnTop_Config_Command_UpdateMappingFields;
use Cardwall_OnTop_Config_TrackerMappingFreestyle;
use Cardwall_OnTop_Config_TrackerMappingStatus;
use Cardwall_OnTop_Config_ValueMapping;
use HTTPRequest;
use TestHelper;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesNoUpdateTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    #[\Override]
    protected function setUp(): void
    {
        $this->dao->method('searchMappingFields')->with($this->tracker_id)->willReturn(TestHelper::arrayToDar([
            'cardwall_tracker_id' => 666,
            'tracker_id'          => 69,
            'field_id'            => 321,
        ]));

        $existing_mappings = [
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, [], [], $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle(
                $this->story_tracker,
                [],
                [
                    9001 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9001), 11),
                    9002 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9002), 11),
                    9007 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9007), 12),
                ],
                $this->stage_field
            ),
        ];

        $this->command = new Cardwall_OnTop_Config_Command_UpdateMappingFields(
            $this->tracker,
            $this->dao,
            $this->value_dao,
            $this->tracker_factory,
            $this->element_factory,
            $existing_mappings
        );
    }

    public function testItDoesntUpdatesMappingFieldValuesWhenMappingDoesntChange(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            [
                '69' => [
                    'field'  => '321',
                    'values' => [
                        '11' => [
                            '9001',
                            '9002',
                        ],
                    ],
                ],
            ]
        );
        $this->value_dao->expects($this->never())->method('deleteAllFieldValues');
        $this->value_dao->expects($this->never())->method('save');
        $this->command->execute($request);
    }

    public function testItUpdatesMappingFieldValuesWhenThereIsANewValue(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            [
                '69' => [
                    'field'  => '321',
                    'values' => [
                        '11' => [
                            '9001',
                            '9002',
                            '9003',
                        ],
                    ],
                ],
            ]
        );
        $this->value_dao->expects($this->once())->method('deleteAllFieldValues');
        $this->value_dao->expects($this->exactly(3))->method('save');
        $this->command->execute($request);
    }

    public function testItUpdatesMappingFieldValuesWhenAValueIsRemoved(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            [
                '69' => [
                    'field'  => '321',
                    'values' => [
                        '11' => [
                            '9001',
                        ],
                    ],
                ],
            ]
        );
        $this->value_dao->expects($this->once())->method('deleteAllFieldValues');
        $this->value_dao->expects($this->once())->method('save');
        $this->command->execute($request);
    }

    private function buildStaticListFieldValue(int $id): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return ListStaticValueBuilder::aStaticValue('Label')->withId($id)->build();
    }
}
