<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker_FormElement_Field_List;

final class ColumnFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItShouldNotFatalErrorOnInvalidBindValue(): void
    {
        $this->expectNotToPerformAssertions();
        $filter = [123, 234];
        $bind   = $this->createMock(\Tracker_FormElement_Field_List_Bind::class);

        $field = $this->createMock(Tracker_FormElement_Field_List::class);
        $field->method('getBind')->willReturn($bind);
        $field->method('isNone')->willReturn(false);
        $field->method('getDecorators')->willReturn([]);
        $bind->method('getValue')->willThrowException(new \Tracker_FormElement_InvalidFieldValueException());

        $dao            = $this->createMock(\Cardwall_OnTop_ColumnDao::class);
        $column_factory = new \Cardwall_OnTop_Config_ColumnFactory($dao);
        $column_factory->getFilteredRendererColumns($field, $filter);
    }
}
