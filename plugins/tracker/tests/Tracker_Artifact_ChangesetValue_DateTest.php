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
Mock::generate('Tracker_FormElement_Field_Date');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class Tracker_Artifact_ChangesetValue_DateTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    function tearDown() {
    }
    
    function testDates() {
        $GLOBALS['Language']->setReturnValueAt(0, 'getText', "Y-m-d", array('system', 'datefmt_short'));
        $GLOBALS['Language']->setReturnValueAt(1, 'getText', "d/m/Y", array('system', 'datefmt_short'));
        $GLOBALS['Language']->setReturnValueAt(2, 'getText', "Y-m-d", array('system', 'datefmt_short'));
        $field = new MockTracker_FormElement_Field_Date();
        $date = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, 1221221466);
        $this->assertEqual($date->getTimestamp(), 1221221466);
        $this->assertEqual($date->getDate(), "2008-09-12");
        $this->assertEqual($date->getDate(), "12/09/2008");
        $this->assertEqual($date->getSoapValue(), 1221221466);
        $this->assertEqual($date->getValue(), "2008-09-12");
        
        $null_date = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, null);
        $this->assertNull($null_date->getTimestamp());
        $this->assertEqual($null_date->getDate(), '');
        $this->assertEqual($null_date->getSoapValue(), '');
    }
    
    function testNoDiff() {
        $field = new MockTracker_FormElement_Field_Date();
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, 1221221466);
        $this->assertFalse($date_1->diff($date_2));
        $this->assertFalse($date_2->diff($date_1));
    }
    
    function testDiff() {
        $tz = ini_get('date.timezone');
        ini_set('date.timezone', 'Europe/Paris');
        
        $GLOBALS['Language']->setReturnValue('getText', "changed from", array('plugin_tracker_artifact','changed_from'));
        $GLOBALS['Language']->setReturnValue('getText', "to", array('plugin_tracker_artifact','to'));        
        $GLOBALS['Language']->setReturnValue('getText', "Y-m-d", array('system', 'datefmt_short'));
        
        $field = new MockTracker_FormElement_Field_Date();
        $date_1 = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, 1221221466);
        $date_2 = new Tracker_Artifact_ChangesetValue_Date(111, $field, false, 1234567890);
        $this->assertEqual($date_1->diff($date_2), 'changed from 2009-02-14 to 2008-09-12');
        $this->assertEqual($date_2->diff($date_1), 'changed from 2008-09-12 to 2009-02-14');
        
        ini_set('date.timezone', $tz);
    }
}
?>
