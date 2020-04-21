<?php
/**
 * Copyright (c) Tuleap, 2019-present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

final class Tracker_FormElement_Field_List_Bind_UgroupsValueTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testJSonReturnsBothNameAndShortName(): void
    {
        $id         = 123;
        $name       = 'The smurfs';
        $short_name = 'blueThings';
        $is_hidden  = false;

        $ugroup = Mockery::mock(ProjectUGroup::class);
        $ugroup->shouldReceive('getTranslatedName')->andReturn($name);
        $ugroup->shouldReceive('getName')->andReturn($short_name);

        $value = new Tracker_FormElement_Field_List_Bind_UgroupsValue($id, $ugroup, $is_hidden);
        $value->setId($id);
        $this->assertEquals('{"id":123,"value":"b123","caption":"The smurfs","rest_value":"blueThings"}', json_encode($value->fetchForOpenListJson()));
    }
}
