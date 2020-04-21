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
final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateFieldTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase
{
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    public function testItUpdatesMappingFields(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            array(
                '42' => array(
                    'field' => '123',
                ),
                '69' => array(
                    'field' => '321',
                ),
            )
        );
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker_id)->andReturns(TestHelper::arrayToDar(
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 42,
                'field_id'            => 100
            ),
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => null
            )
        ));
        $this->dao->shouldReceive('save')->with($this->tracker_id, 42, 123)->once()->andReturns(true);
        $this->dao->shouldReceive('save')->with($this->tracker_id, 69, 321)->once()->andReturns(true);
        $this->command->execute($request);
    }

    public function testItDoesntUpdatesMappingFieldsIfItIsNotNeeded(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            array(
                '42' => array(
                    'field' => '123',
                ),
                '69' => array(
                    'field' => '322',
                ),
            )
        );
        $this->dao->shouldReceive('searchMappingFields')->with($this->tracker_id)->andReturns(TestHelper::arrayToDar(
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 42,
                'field_id'            => 123
            ),
            array(
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 321
            )
        ));
        $this->dao->shouldReceive('save')->with($this->tracker_id, 69, 322)->once();
        $this->command->execute($request);
    }
}
