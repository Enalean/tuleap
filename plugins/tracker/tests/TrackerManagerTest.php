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

require_once(dirname(__FILE__).'/../include/Tracker/TrackerManager.class.php');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Interface');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker_Report');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_ArtifactFactory');
Mock::generate('Tracker_ReportFactory');
Mock::generatePartial('TrackerManager', 
                      'TrackerManagerTestVersion', 
                      array(
                          'getTrackerFactory',
                          'getTracker_FormElementFactory',
                          'getArtifactFactory',
                          'getArtifactReportFactory',
                          'getProject',
                          'displayAllTrackers',
                      )
);
require_once('common/include/Codendi_Request.class.php');
Mock::generate('Codendi_Request');
require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/include/Response.class.php');
Mock::generate('Response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/layout/Layout.class.php');
Mock::generate('Layout');

class TrackerManagerTest extends UnitTestCase {
    
    public function setup() {
        $this->user = new MockUser($this);
        $this->user->setReturnValue('getId', 666);
        
        $this->artifact = new MockTracker_Artifact($this);
        $af = new MockTracker_ArtifactFactory($this);
        $af->setReturnReference('getArtifactById', $this->artifact, array('1'));
        
        $this->report = new MockTracker_Report($this);
        $rf = new MockTracker_ReportFactory($this);
        $rf->setReturnReference('getReportById', $this->report, array('2', $this->user->getId(), true));
        
        $this->tracker = new MockTracker($this);
        $tf = new MockTrackerFactory($this);
        $tf->setReturnReference('getTrackerById', $this->tracker, array(3));
        
        $this->formElement = new MockTracker_FormElement_Interface($this);
        $ff = new MockTracker_FormElementFactory($this);
        $ff->setReturnReference('getFormElementById', $this->formElement, array('4'));
        
        $this->artifact->setReturnReference('getTracker', $this->tracker);
        $this->report->setReturnReference('getTracker', $this->tracker);
        $this->formElement->setReturnReference('getTracker', $this->tracker);
        $this->tracker->setReturnValue('getGroupId', 5);
        
        $this->tm = new TrackerManagerTestVersion($this);
        $this->tm->setReturnReference('getTrackerFactory', $tf);
        $this->tm->setReturnReference('getTracker_FormElementFactory', $ff);
        $this->tm->setReturnReference('getArtifactFactory', $af);
        $this->tm->setReturnReference('getArtifactReportFactory', $rf);
        
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['HTML'] = new MockLayout();
    }
    public function tearDown() {
        unset($this->user);
        unset($this->artifact);
        unset($this->tracker);
        unset($this->formElement);
        unset($this->report);
        unset($this->tm);
    }
    
    public function testProcessArtifact() {
        $this->artifact->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        $this->report->expectNever('process');
        $store_in_session = true;
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '1', array('aid'));
        $request_artifact->setReturnValue('get', '2', array('report'));
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->artifact->setReturnValue('userCanView', true);
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessReport() {
        $this->artifact->expectNever('process');
        $this->report->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '2', array('report'));
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessTracker() {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectOnce('process');
        $this->formElement->expectNever('process');
        
        $this->tracker->setReturnValue('userCanView', true);
        $this->tracker->expectOnce('userCanView');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessTracker_with_no_permissions_to_view() {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process'); //user can't view the tracker. so don't process the request in tracker
        $this->formElement->expectNever('process');
        
        $this->tracker->setReturnValue('userCanView', false);
        $this->tracker->expectOnce('userCanView');
        $GLOBALS['Response']->expect('addFeedback', array('error', '*'));
        $GLOBALS['Language']->expect('getText', array('plugin_tracker_common_type', 'no_view_permission'));
        $this->tm->expectOnce('displayAllTrackers');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', 3, array('tracker'));
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tm->process($request_artifact, $this->user);
    }
        
    public function testProcessField() {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectOnce('process');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '4', array('formElement'));
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessItself() {
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testSearch() {
        $request = new MockCodendi_Request($this);
        $request->setReturnValue('exist', true, array('tracker'));
        $request->setReturnValue('get', 3, array('tracker'));
        $this->tracker->setReturnValue('userCanView', true, array($this->user));
        
        $this->tracker->expectOnce('displaySearch');
        $this->tm->search($request, $this->user);
    }
    
    public function testSearchUserCannotViewTracker() {
        $request = new MockCodendi_Request($this);
        $request->setReturnValue('exist', true, array('tracker'));
        $request->setReturnValue('get', 3, array('tracker'));
        
        $this->tracker->expectNever('displaySearch');
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect');
        $this->tracker->setReturnValue('userCanView', false, array($this->user));
        $this->tm->search($request, $this->user);
    }
    
    public function testSearchAllTrackerDisplaySearchNotCalled() {
        $request = new MockCodendi_Request($this);
        $request->setReturnValue('exist', false, array('tracker'));
        $this->tracker->setReturnValue('userCanView', true, array($this->user));
        
        $this->tracker->expectNever('displaySearch');
        $this->tm->search($request, $this->user);
    }
}

?>