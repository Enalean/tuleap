<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * 
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


require_once('common/layout/Layout.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php');
require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/View/Admin.class.php');
Mock::generate('Tracker');
Mock::generate('Project');
Mock::generate('Layout');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/plugins/tracker/');
}

class Tracker_FormElement_View_AdminTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $GLOBALS['Language']->setReturnValue(
            'getText', 
            'This field is shared from tracker Bugs from project Tuleap', 
            array(
                'plugin_tracker_include_type', 
                'field_copied_from', 
                array('Bugs', 'Tuleap')
            )
        );
    }
    
    public function tearDown() {
         parent::tearDown();
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
        return new Tracker_FormElement_View_Admin($element, array());
    }
}

class FakeFormElement extends Tracker_FormElement_Field_String {
    
    public static function getFactoryIconUseIt() {
        // just here to avoid undesired behaviour in test
    }

    public static function getFactoryLabel() {
        
    }
    
    public function getProperties() {
        return array();
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