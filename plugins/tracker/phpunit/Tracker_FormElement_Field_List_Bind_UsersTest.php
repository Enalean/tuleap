<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class Tracker_FormElement_Field_List_Bind_UsersTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    public function testRetrievingDefaultRESTValuesDoesNotHitTheDBWhenNoDefaultValuesIsSet()
    {
        $list_field     = Mockery::mock(Tracker_FormElement_Field_List::class);
        $default_values = [];

        $bind_users = new Tracker_FormElement_Field_List_Bind_Users($list_field, '', $default_values, []);

        $this->assertEmpty($bind_users->getDefaultValues());
        $this->assertEmpty($bind_users->getDefaultRESTValues());
    }
}
