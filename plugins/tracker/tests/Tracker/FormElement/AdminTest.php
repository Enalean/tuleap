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
Mock::generate('Tracker');
Mock::generate('Project');
class Tracker_FormElement_AdminTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        $language = new BaseLanguage('en_US', 'en_US');
        $language->text_array['plugin_tracker_include_type']['field_copied_from'] = 'This field is shared from tracker $1 from project $2';
        $GLOBALS['Language'] = $language;
    }
    
    public function testForSharedFieldsItDisplaysOriginalTrackerAndProjectName() {
        $admin = $this->GivenAnAdminWithOriginalProjectAndTracker('Tuleap', 'Bugs');
        $result = $admin->fetchAdminForShared();
        $this->assertPattern("%Bugs%", $result);
        $this->assertPattern("%Tuleap%", $result);
    }

    public function GivenAnAdminWithOriginalProjectAndTracker($projectName, $trackerName) {
        $tracker = new MockTracker();
        $tracker->setReturnValue('getName', $trackerName);
        $project = new MockProject();
        $project->setReturnValue('getPublicName', $projectName);
        $element = new FakeFormElement(null, null, null, null, null, null, null, null, null, null, null, null);
        $element->setOriginalTracker($tracker);
        $element->setOriginalProject($project);
        return new Tracker_FormElement_Admin($element);
    }
}
class FakeFormElement extends Tracker_FormElement_Field_String {
    public static function getFactoryIconUseIt() {
        // just here to avoid undesired behaviour in test
    }

    public function setOriginalProject($project) {
        $this->originalProject = $project;
    }
    public function getOriginalProject() {
        return $this->originalProject;
    }
    public function setOriginalTracker($tracker) {
        $this->originalTracker = $tracker;
    }
    public function getOriginalTracker() {
        return $this->originalTracker;
    }
}
?>