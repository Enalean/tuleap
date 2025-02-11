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

final class Cardwall_OnTop_Config_Command_UpdateMappingFields_UpdateFieldTest extends Cardwall_OnTop_Config_Command_UpdateMappingFieldsTestBase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    public function testItUpdatesMappingFields(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            [
                '42' => [
                    'field' => '123',
                ],
                '69' => [
                    'field' => '321',
                ],
            ]
        );
        $this->dao->method('searchMappingFields')->with($this->tracker_id)->willReturn(TestHelper::arrayToDar(
            [
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 42,
                'field_id'            => 100,
            ],
            [
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => null,
            ]
        ));
        $matcher = self::exactly(2);
        $this->dao->expects($matcher)->method('save')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->tracker_id, $parameters[0]);
                self::assertSame(42, $parameters[1]);
                self::assertSame(123, $parameters[2]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->tracker_id, $parameters[0]);
                self::assertSame(69, $parameters[1]);
                self::assertSame(321, $parameters[2]);
            }
            return true;
        });
        $this->value_dao->method('delete');
        $this->command->execute($request);
    }

    public function testItDoesntUpdatesMappingFieldsIfItIsNotNeeded(): void
    {
        $request = new HTTPRequest();
        $request->set(
            'mapping_field',
            [
                '42' => [
                    'field' => '123',
                ],
                '69' => [
                    'field' => '322',
                ],
            ]
        );
        $this->dao->method('searchMappingFields')->with($this->tracker_id)->willReturn(TestHelper::arrayToDar(
            [
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 42,
                'field_id'            => 123,
            ],
            [
                'cardwall_tracker_id' => 666,
                'tracker_id'          => 69,
                'field_id'            => 321,
            ]
        ));
        $this->dao->expects(self::once())->method('save')->with($this->tracker_id, 69, 322);
        $this->command->execute($request);
    }
}
