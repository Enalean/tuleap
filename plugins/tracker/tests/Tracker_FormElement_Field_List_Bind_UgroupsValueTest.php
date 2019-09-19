<?php
/**
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
require_once('bootstrap.php');

class Tracker_FormElement_Field_List_Bind_UgroupsValueTest extends TuleapTestCase
{

    public function testJSonReturnsBothNameAndShortName()
    {
        $id         = 123;
        $name       = 'The smurfs';
        $short_name = 'blueThings';
        $is_hidden  = false;

        $ugroup = mock('ProjectUGroup');
        stub($ugroup)->getTranslatedName()->returns($name);
        stub($ugroup)->getName()->returns($short_name);

        $value = new Tracker_FormElement_Field_List_Bind_UgroupsValue($id, $ugroup, $is_hidden);
        $value->setId($id);
        $this->assertEqual(json_encode($value->fetchForOpenListJson()), '{"id":123,"value":"b123","caption":"The smurfs","rest_value":"blueThings"}');
    }
}
