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
Mock::generate('Tracker_URL');
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
                          'getUrl',
                          'getTrackerFactory',
                          'getTracker_FormElementFactory',
                          'getArtifactFactory',
                          'getArtifactReportFactory',
                          'getProject',
                          'displayAllTrackers',
                          'checkServiceEnabled',
                          'getCrossSearchController'
                      )
);
require_once dirname(__FILE__) .'/../include/Tracker/CrossSearch/SearchController.class.php';
Mock::generate('Tracker_CrossSearch_SearchController');
require_once('common/include/Codendi_Request.class.php');
Mock::generate('Codendi_Request');
require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/layout/Layout.class.php');
Mock::generate('Layout');
require_once('common/project/Project.class.php');
Mock::generate('Project');
Mock::generate('ReferenceManager');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/coin');
}

class TrackerManagerTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->user = new MockUser($this);
        $this->user->setReturnValue('getId', 666);
        
        $this->url = new MockTracker_URL();
        
        $project = new MockProject();
        
        $this->artifact = new MockTracker_Artifact($this);
        $af = new MockTracker_ArtifactFactory($this);
        $af->setReturnReference('getArtifactById', $this->artifact, array('1'));
        
        $this->report = new MockTracker_Report($this);
        $rf = new MockTracker_ReportFactory($this);
        $rf->setReturnReference('getReportById', $this->report, array('2', $this->user->getId(), true));
        
        $this->tracker = new MockTracker($this);
        $this->tracker->setReturnValue('isActive', true);
        $this->tracker->setReturnValue('getTracker', $this->tracker);
        $tf = new MockTrackerFactory($this);
        $tf->setReturnReference('getTrackerById', $this->tracker, array(3));
        
        $this->formElement = new MockTracker_FormElement_Interface($this);
        $ff = new MockTracker_FormElementFactory($this);
        $ff->setReturnReference('getFormElementById', $this->formElement, array('4'));
        
        $this->artifact->setReturnReference('getTracker', $this->tracker);
        $this->report->setReturnReference('getTracker', $this->tracker);
        $this->formElement->setReturnReference('getTracker', $this->tracker);
        $this->tracker->setReturnValue('getGroupId', 5);
        $this->tracker->setReturnReference('getProject', $project);
        
        $this->tm = new TrackerManagerTestVersion($this);
        $this->tm->setReturnReference('getUrl', $this->url);
        $this->tm->setReturnReference('getTrackerFactory', $tf);
        $this->tm->setReturnReference('getTracker_FormElementFactory', $ff);
        $this->tm->setReturnReference('getArtifactFactory', $af);
        $this->tm->setReturnReference('getArtifactReportFactory', $rf);
        $this->tm->setReturnValue('checkServiceEnabled', true);
        
        $GLOBALS['HTML'] = new MockLayout();
    }
    public function tearDown() {
        unset($this->url);
        unset($this->user);
        unset($this->artifact);
        unset($this->tracker);
        unset($this->formElement);
        unset($this->report);
        unset($this->tm);
        parent::tearDown();
    }
    
    public function testProcessArtifact() {
        $this->artifact->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        $this->report->expectNever('process');
        
        $request_artifact = new MockCodendi_Request($this);
        $request_artifact->setReturnValue('get', '1', array('aid'));
        $this->artifact->setReturnValue('userCanView', true);
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->artifact);
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessReport() {
        $this->artifact->expectNever('process');
        $this->report->expectOnce('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        
        $request_artifact = new MockCodendi_Request($this);
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->report);
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
        
        $this->url->setReturnValue('getDispatchableFromRequest', $this->tracker);
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
        
        $this->url->setReturnValue('getDispatchableFromRequest', $this->tracker);
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
        $this->tracker->setReturnValue('userCanView', true);
        $this->url->setReturnValue('getDispatchableFromRequest', $this->formElement);
        $this->tm->process($request_artifact, $this->user);
    }
    
    public function testProcessItself() {
        $request_artifact = new MockCodendi_Request($this);
        
        $tm = TestHelper::getPartialMock('TrackerManager', array('getProject', 'displayAllTrackers', 'checkServiceEnabled'));
        $project = new MockProject();
        $tm->expectOnce('getProject');
        $tm->setReturnValue('getProject', $project, array(5));
        $tm->setReturnValue('checkServiceEnabled', true, array($project, $request_artifact));
        $tm->expectOnce('displayAllTrackers', array($project, $this->user));
        
        $this->artifact->expectNever('process');
        $this->report->expectNever('process');
        $this->tracker->expectNever('process');
        $this->formElement->expectNever('process');
        
        $request_artifact->setReturnValue('get', '5', array('group_id'));
        $tm->process($request_artifact, $this->user);
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
    
    /**
     * Given I have 3 plugin_tracker_artifact references in the template project
     * - bug
     * - issue 
     * - task
     * And 'bug' correspond to 'Bug' tracker
     * And 'task' correspond to 'Task' tracker
     * And 'issue' was created by hand by the admin
     * And 'Bug' tracker is instanciated for new project
     * And 'Task' tracker is not instanciated for new project
     * 
     * Then 'issue' reference is created
     * And 'bug' reference is not created (it's up to tracker creation to create the reference)
     * And 'task' reference is not created (it's up to tracker creation to create the reference)
     *
     */
    public function testDuplicateCopyReferences() {
        $source_project_id       = 100;
        $destinatnion_project_id = 120;
        $u_group_mapping         = array();
        
        $tm = TestHelper::getPartialMock('TrackerManager', array('getTrackerFactory', 'getReferenceManager'));
        
        $tf = new MockTrackerFactory();
        $tf->expectOnce('duplicate');
        $tm->setReturnValue('getTrackerFactory', $tf);
        
        
        $r1 = new Reference(101 , 'bug',   'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1 , 100);
        $r2 = new Reference(102 , 'issue', 'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1 , 100);
        $r3 = new Reference(103 , 'task',  'desc', '/plugins/tracker/?aid=$1&group_id=$group_id', 'P', 'plugin_tracker', 'plugin_tracker_artifact', 1 , 100);

        $rm = new MockReferenceManager();
        $rm->expectOnce('getReferencesByGroupId', array($source_project_id));
        $rm->setReturnValue('getReferencesByGroupId', array($r1, $r2, $r3));
        $tm->setReturnValue('getReferenceManager', $rm);
        
        $t1 = new MockTracker();
        $t1->setReturnValue('getItemName', 'bug');
        $t1->setReturnValue('mustBeInstantiatedForNewProjects', true);
        $t2 = new MockTracker();
        $t2->setReturnValue('getItemName', 'task');
        $t2->setReturnValue('mustBeInstantiatedForNewProjects', false);
        
        $tf->setReturnValue('getTrackersByGroupId', array($t1, $t2), $source_project_id);
        
        $rm->expectOnce('createReference', array($r2));
        
        $tm->duplicate($source_project_id, $destinatnion_project_id, $u_group_mapping);
    }
    
    public function testProcessCrossSearch() {
        $project    = new MockProject();
        $request    = new Codendi_Request(array('func'     => 'cross-search',
                                                'group_id' => '101'));
        $user       = null;
        $controller = new MockTracker_CrossSearch_SearchController();
        
        $this->url->throwOn('getDispatchableFromRequest', new Tracker_NoMachingResourceException());
        $this->tm->setReturnValue('getProject', $project);
        $this->tm->setReturnValue('checkServiceEnabled', true);
        $this->tm->setReturnValue('getCrossSearchController', $controller);
        
        $controller->expectOnce('search');
        $this->tm->process($request, $user);
    }
}

?>