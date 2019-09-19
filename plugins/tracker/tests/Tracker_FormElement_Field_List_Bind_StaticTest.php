<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
require_once('bootstrap.php');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_FormElement_Field_List_Bind_StaticValue');
Mock::generate('Tracker_FormElement_Field_List');
Mock::generate('Tracker_FormElement_Field_List_Bind_Static_ValueDao');

class Tracker_FormElement_Field_List_Bind_StaticTest extends TuleapTestCase
{

    public function testGetBindValues()
    {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $bv2 = new MockTracker_FormElement_Field_List_Bind_StaticValue();
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(101 => $bv1, 102 => $bv2);
        $static = new Tracker_FormElement_Field_List_Bind_Static($field, $is_rank_alpha, $values, $default_values, $decorators);
        $this->assertEqual($static->getBindValues(), $values);
        $this->assertEqual($static->getBindValues(array()), array(), 'Dont give more than what we are asking');
        $this->assertEqual($static->getBindValues(array(102)), array(102 => $bv2));
        $this->assertEqual($static->getBindValues(array(666)), array(), 'What do we have to do with unknown value?');
    }

    function testGetFieldData()
    {
        $bv1 = aFieldListStaticValue()->withLabel('1 - Ordinary')->build();
        $bv2 = aFieldListStaticValue()->withLabel('9 - Critical')->build();
        $values = array(13564 => $bv1, 13987 => $bv2);
        $f = aBindStatic()->withValues($values)->build();
        $this->assertEqual('13564', $f->getFieldData('1 - Ordinary', false));
    }

    function testGetFieldDataMultiple()
    {
        $bv1 = aFieldListStaticValue()->withLabel('Admin')->build();
        $bv2 = aFieldListStaticValue()->withLabel('Tracker')->build();
        $bv3 = aFieldListStaticValue()->withLabel('User Interface')->build();
        $bv4 = aFieldListStaticValue()->withLabel('Docman')->build();
        $values = array(13564 => $bv1, 13987 => $bv2, 125 => $bv3, 666 => $bv4);

        $res = array('13564', '125', '666');
        $f = aBindStatic()->withValues($values)->build();
        $this->assertEqual($res, $f->getFieldData('Admin,User Interface,Docman', true));
    }
}

class Tracker_FormElement_Field_List_Bind_Static_AddBindValue extends TuleapTestCase
{

    public function itAddsANewValue()
    {
        $field         = aSelectBoxField()->withId(101)->build();
        $is_rank_alpha = $values = $default_values = $decorators = '';
        $value_dao     = new MockTracker_FormElement_Field_List_Bind_Static_ValueDao();
        $value_dao->setReturnValue('create', 321);
        $bind_static = partial_mock(
            'Tracker_FormElement_Field_List_Bind_Static',
            array('getValueDao'),
            array($field, $is_rank_alpha, $values, $default_values, $decorators)
        );
        $bind_static->setReturnValue('getValueDao', $value_dao);

        $value_dao->expect('create', array(
            101,
            'intermodulation',
            '*',
            '*',
            '*'
        ));

        $new_id = $bind_static->addValue(' intermodulation	');

        $this->assertEqual($new_id, 321);
    }
}

class Tracker_FormElement_Field_List_Bind_Static_ImportInvalidValue extends TuleapTestCase
{

    public function itDoesntCrashWhenInvalidValueShouldBePrinted()
    {
        $field         = aSelectBoxField()->withId(101)->build();
        $bind = new Tracker_FormElement_Field_List_Bind_Static($field, 0, array(), null, null);
        $this->assertEqual('-', $bind->formatArtifactValue(0));
    }
}
