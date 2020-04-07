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
final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    public function testItUpdatesMappingFieldValues(): void
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
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker_id)->andReturns(TestHelper::arrayToDar(
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 321
            )
        ));
        $this->value_dao->shouldReceive('deleteAllFieldValues')->with($this->tracker_id, 69, 321, 11)->once();
        $this->value_dao->shouldReceive('save')->with($this->tracker_id, 69, 321, 9001, 11)->once();
        $this->value_dao->shouldReceive('save')->with($this->tracker_id, 69, 321, 9002, 11)->once();
        $this->command->execute($request);
    }

    public function testItDoesntUpdateMappingValuesIfTheFieldChange(): void
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
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker_id)->andReturns(TestHelper::arrayToDar(
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 666,
            )
        ));
        $this->dao->shouldReceive('save')->andReturns(true);
        $this->value_dao->shouldReceive('delete')->with($this->tracker_id, 69)->once();
        $this->value_dao->shouldReceive('deleteAllFieldValues')->never();
        $this->value_dao->shouldReceive('save')->never();
        $this->command->execute($request);
    }
}
