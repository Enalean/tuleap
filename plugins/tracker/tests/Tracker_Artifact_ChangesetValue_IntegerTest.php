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

Mock::generate('Tracker_FormElement_Field_Integer');

class Tracker_Artifact_ChangesetValue_IntegerTest extends TuleapTestCase {
    
    function testIntegers() {
        $field = new MockTracker_FormElement_Field_Integer();
        $integer = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, 42);
        $this->assertEqual($integer->getInteger(), 42);
        $this->assertNotIdentical($integer->getInteger(), '42');
        $this->assertEqual($integer->getSoapValue(), '42');
        $this->assertIdentical($integer->getSoapValue(), '42');
        $this->assertIdentical($integer->getValue(), 42);
        
        $string_int = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, '55');
        $this->assertEqual($string_int->getInteger(), 55);
        $this->assertEqual($string_int->getInteger(), '55');
        $this->assertNotIdentical($string_int->getInteger(), '55');
        $this->assertIdentical($string_int->getSoapValue(), '55');
        $this->assertIdentical($string_int->getValue(), 55);
        
        $null_int = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, null);
        $this->assertNull($null_int->getInteger());
        $this->assertEqual($null_int->getSoapValue(), '');
        $this->assertIdentical($null_int->getSoapValue(), '');
        $this->assertNull($null_int->getValue());
    }
    
    function testNoDiff() {
        $field = new MockTracker_FormElement_Field_Integer();
        $int_1 = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, 54);
        $int_2 = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, 54);
        $this->assertFalse($int_1->diff($int_2));
        $this->assertFalse($int_2->diff($int_1));
    }
    
    function testDiff() {
        $GLOBALS['Language']->setReturnValue('getText', 'changed from', array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', 'to', array('plugin_tracker_artifact','to'));
        
        $field = new MockTracker_FormElement_Field_Integer();
        $int_1 = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, 66);
        $int_2 = new Tracker_Artifact_ChangesetValue_Integer(111, $field, false, 666);
        $this->assertEqual($int_1->diff($int_2), 'changed from 666 to 66');
    }
}
?>