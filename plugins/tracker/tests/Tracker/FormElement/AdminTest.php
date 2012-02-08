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
require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/admin/Admin.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php');
Mock::generate('Tracker_FormElement_Field_String');
Mock::generate('Tracker');
class Tracker_FormElement_AdminTest extends TuleapTestCase {
    public function testForSharedFieldsItDisplaysOriginalTrackerName() {
        $originalTrackerName = 'Bugs';
        $tracker = new MockTracker();
        $tracker->setReturnValue('getName', $originalTrackerName);
        $originalField = new MockTracker_FormElement_Field_String();
        $originalField->setReturnValue('getTracker', $tracker);
        $element = new FakeFormElement(null, null, null, null, null, null, null, null, null, null, null, null);
        $element->setOriginalField($originalField);
//        $element->setReturnValue('getOriginalField', $originalField);
        
        $admin = new Tracker_FormElement_Admin($element);
        $this->assertPattern("%$originalTrackerName%", $admin->fetchAdminForShared());
    }

}
class FakeFormElement extends Tracker_FormElement_Field_String {
    public static function getFactoryIconUseIt() {
        
    }
}
?>