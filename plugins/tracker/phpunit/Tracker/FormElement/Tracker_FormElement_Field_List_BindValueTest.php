<?php
/**
 * Copyright (c) Enalean, 2019-present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

final class Tracker_FormElement_Field_List_BindValueTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testJSon(): void
    {
        $id          = 123;
        $label       = 'Reopen';
        $value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $value->shouldReceive('getLabel')->andReturn($label);
        $value->shouldReceive('getId')->andReturn($id);
        $this->assertEquals('{"id":123,"value":"b123","caption":"Reopen","rest_value":"Reopen"}', json_encode($value->fetchForOpenListJson()));
    }
}
