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
Mock::generate('Tracker_Artifact');

Mock::generate('Tracker_FormElement_Field_Float');

class Tracker_Artifact_ChangesetValue_FloatTest extends TuleapTestCase {
    
    function testFloats() {
        $field = new MockTracker_FormElement_Field_Float();
        
        $float = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 1.1234);
        $this->assertEqual($float->getFloat(), 1.1234);
        $this->assertNotIdentical($float->getFloat(), '1.1234');
        $this->assertEqual($float->getSoapValue(), array('value' => '1.1234'));
        $this->assertIdentical($float->getValue(), '1.1234');
        
        $long_float = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 9.54321);
        $this->assertEqual($long_float->getFloat(), 9.54321);
        $this->assertNotIdentical($long_float->getFloat(), '9.54321');
        $this->assertEqual($long_float->getSoapValue(), array('value' => '9.54321'));
        $this->assertEqual($long_float->getValue(), '9.5432');
        
        $int_float = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 42);
        $this->assertEqual($int_float->getFloat(), 42);
        $this->assertEqual($int_float->getFloat(), 42.000);
        $this->assertIdentical($int_float->getFloat(), 42.000);
        $this->assertNotIdentical($int_float->getFloat(), '42');
        $this->assertEqual($int_float->getSoapValue(), array('value' => '42'));
        $this->assertEqual($int_float->getValue(), '42.0000');
        
        $string_float = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, '123.456');
        $this->assertEqual($string_float->getFloat(), 123.456);
        $this->assertNotEqual($string_float->getFloat(), 123.457);
        $this->assertEqual($string_float->getFloat(), '123.456');
        $this->assertNotIdentical($string_float->getFloat(), '123.456');
        $this->assertEqual($string_float->getSoapValue(), array('value' => '123.456'));
        $this->assertEqual($string_float->getValue(), '123.456');
        
        $null_float = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, null);
        $this->assertNull($null_float->getFloat());
        $this->assertEqual($null_float->getSoapValue(), array('value' => ''));
        $this->assertIdentical($null_float->getSoapValue(), array('value' => ''));
        $this->assertIdentical($null_float->getValue(), '');
    }
    
    function testNoDiff() {
        $field = new MockTracker_FormElement_Field_Float();
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 456.789);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 456.789);
        $this->assertFalse($float_1->diff($float_2));
        $this->assertFalse($float_2->diff($float_1));
    }
    
    function testDiff() {
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        
        $field = new MockTracker_FormElement_Field_Float();
        $float_1 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 987.321);
        $float_2 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 987);
        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        
        $this->assertEqual($float_1->diff($float_2), 'changed from 987.0000 to 987.3210');
        $this->assertEqual($float_2->diff($float_1), 'changed from 987.3210 to 987.0000');
        
        $float_3 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 456.54321);
        $float_4 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 456.54322);
        $this->assertFalse($float_3->diff($float_4));
        $this->assertFalse($float_4->diff($float_3));
        
        $float_5 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 987.4321);
        $float_6 = new Tracker_Artifact_ChangesetValue_Float(111, $field, false, 987.4329);
        $this->assertEqual($float_5->diff($float_6), 'changed from 987.4329 to 987.4321');
        $this->assertEqual($float_6->diff($float_5), 'changed from 987.4321 to 987.4329');
    }
}
?>