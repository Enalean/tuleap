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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesNoUpdateTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker_id)->andReturns(TestHelper::arrayToDar(
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 321
            )
        ));

        $existing_mappings = array(
            42 => new Cardwall_OnTop_Config_TrackerMappingStatus($this->task_tracker, array(), array(), $this->status_field),
            69 => new Cardwall_OnTop_Config_TrackerMappingFreestyle(
                $this->story_tracker,
                array(),
                array(
                    9001 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9001), 11),
                    9002 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9002), 11),
                    9007 => new Cardwall_OnTop_Config_ValueMapping($this->buildStaticListFieldValue(9007), 12),
                ),
                $this->stage_field
            ),
        );

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
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002'
                        ),
                    )
                ),
            )
        );
        $this->value_dao->shouldReceive('deleteAllFieldValues')->never();
        $this->value_dao->shouldReceive('save')->never();
        $this->command->execute($request);
    }

    public function testItUpdatesMappingFieldValuesWhenThereIsANewValue(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                            '9002',
                            '9003'
                        ),
                    )
                )
            )
        );
        $this->value_dao->shouldReceive('deleteAllFieldValues')->once();
        $this->value_dao->shouldReceive('save')->times(3);
        $this->command->execute($request);
    }

    public function testItUpdatesMappingFieldValuesWhenAValueIsRemoved(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            array(
                '69' => array(
                    'field' => '321',
                    'values' => array(
                        '11' => array(
                            '9001',
                        ),
                    )
                ),
            )
        );
        $this->value_dao->shouldReceive('deleteAllFieldValues')->once();
        $this->value_dao->shouldReceive('save')->once();
        $this->command->execute($request);
    }

    private function buildStaticListFieldValue(int $id): Tracker_FormElement_Field_List_Bind_StaticValue
    {
        $value = Mockery::mock(Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $value->shouldReceive('getId')->andReturn($id);

        return $value;
    }
}
