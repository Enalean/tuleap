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

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_LastUpdateDate.class.php');
Mock::generatePartial(
    'Tracker_FormElement_Field_LastUpdateDate', 
    'Tracker_FormElement_Field_LastUpdateDateTestVersion', 
    array(
        'getValueDao', 
        'isRequired', 
        'getProperty', 
        'getProperties', 
        'formatDate',
        'getDao',
    )
);
    
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue_Date.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');

class Tracker_FormElement_Field_LastUpdateDateTest extends UnitTestCase {
    
    function testhasChanges() {
        $f = new Tracker_FormElement_Field_LastUpdateDateTestVersion();
        $v = new MockTracker_Artifact_ChangesetValue_Date();
        $this->assertFalse($f->hasChanges($v, null));
    }
    
    function testisValid() {
        $f = new Tracker_FormElement_Field_LastUpdateDateTestVersion();
        $a = new MockTracker_Artifact();
        $this->assertTrue($f->isValid($a, null));
    }
    
}

?>