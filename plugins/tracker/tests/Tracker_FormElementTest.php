<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php'); // workaround for cyclic includes issue

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement.class.php');

Mock::generate('Project');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_Selectbox');

class Tracker_FormElementTest extends UnitTestCase {
    function testGetOriginalProjectAndOriginalTracker() {
        $project = new MockProject();
        $tracker = new MockTracker();
        $tracker->setReturnValue('getProject', $project);
        $original = new MockTracker_FormElement_Field_Selectbox();
        $original->setReturnValue('getTracker', $tracker);
        
        $element = new Tracker_FormElement_Field_Selectbox(null, null, null, null, null, null, null, null, null, null, null, $original);
        
        $this->assertEqual($tracker, $element->getOriginalTracker());
        $this->assertEqual($project, $element->getOriginalProject());
    }
    
    function testGetOriginalFieldIdShouldReturnTheFieldId() {
        $original = new Tracker_FormElement_Field_Selectbox(112, null, null, null, null, null, null, null, null, null, null, null);
        $element = new Tracker_FormElement_Field_Selectbox(null, null, null, null, null, null, null, null, null, null, null, $original);
        $this->assertEqual($element->getOriginalFieldId(), 112);
    }
    
    function testGetOriginalFieldIdShouldReturn0IfNoOriginalField() {
        $element = new Tracker_FormElement_Field_Selectbox(null, null, null, null, null, null, null, null, null, null, null, null);
        $this->assertEqual($element->getOriginalFieldId(), 0);
    }
}
?>
