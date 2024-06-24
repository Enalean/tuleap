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

use HTTPRequest;
use TestHelper;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;

final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateValuesTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    public function testItUpdatesMappingFieldValues(): void
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
        $this->dao->method('searchMappingFields')->with($this->tracker_id)->willReturn(TestHelper::arrayToDar([
            'cardwall_tracker_id' => 666,
            'tracker_id'          => 69,
            'field_id'            => 321,
        ]));
        $this->value_dao->expects(self::once())->method('deleteAllFieldValues')->with($this->tracker_id, 69, 321, 11);
        $this->value_dao->expects(self::exactly(2))
            ->method('save')
            ->withConsecutive(
                [$this->tracker_id, 69, 321, 9001, 11],
                [$this->tracker_id, 69, 321, 9002, 11],
            );
        $this->command->execute($request);
    }

    public function testItDoesntUpdateMappingValuesIfTheFieldChange(): void
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
        $this->dao->method('searchMappingFields')->with($this->tracker_id)->willReturn(TestHelper::arrayToDar(
            [
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 666,
            ]
        ));
        $this->dao->method('save')->willReturn(true);
        $this->value_dao->expects(self::once())->method('delete')->with($this->tracker_id, 69);
        $this->value_dao->expects(self::never())->method('deleteAllFieldValues');
        $this->value_dao->expects(self::never())->method('save');
        $this->command->execute($request);
    }
}
