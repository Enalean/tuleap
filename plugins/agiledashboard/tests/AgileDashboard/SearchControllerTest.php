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

require_once dirname(__FILE__) . '/../../../tracker/tests/Test_Tracker_Builder.php';
require_once dirname(__FILE__) . '/../../../tracker/tests/Test_Tracker_FormElement_Builder.php';

require_once dirname(__FILE__) . '/../../include/AgileDashboard/SearchController.class.php';
require_once 'common/include/Codendi_Request.class.php';
require_once 'common/project/ProjectManager.class.php';
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');
Mock::generate('AgileDashboard_SearchView');
Mock::generate('AgileDashboard_Search');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_Hierarchy_Dao');

class AgileDashboard_SearchControllerIndexTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->service            = new MockService();
        $this->project            = new MockProject();
        $this->manager            = new MockProjectManager();
        $this->request            = new Codendi_Request(array('group_id' => '66'));
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->search             = new MockAgileDashboard_Search();
        $this->hierarchy_dao      = new MockTracker_Hierarchy_Dao();
        $this->hierarchy_dao->setReturnValue('searchTrackerHierarchy', array());
    }
    
    public function testSearchRendersViewForServiceWithCriteria() {
        $view = new MockAgileDashboard_SearchView();
        $view->expectOnce('render');
                
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
        
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        
        $fields = array(aTextField()->build(), aStringField()->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields, array($this->project));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView', 'getTrackers'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', array());
        
        $controller->search();
    }
    
    public function testSearchRedirectsWithErrorMessageIfServiceIsNotUsed() {
        $this->project->setReturnValue('getService', null, array('plugin_agiledashboard'));
        $this->project->setReturnValue('getUnixName', 'coin');

        $this->manager->setReturnValue('getProject', $this->project, array('66'));

        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);

        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/projects/coin/'));

        $controller->search();
    }
    
    public function testSearchRedirectsToHomepageWhenProjectDoesNotExist() {
        $this->project->setReturnValue('isError', true);
        
        $this->manager->setReturnValue('getProject', $this->project, array('invalid_project_id'));
        
        $this->request = new Codendi_Request(array('group_id' => 'invalid_project_id'));
        
        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/'));
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        
        $controller->search();
    }
    
    public function testSearchActionRendersTheSearchView() {
        $view = new MockAgileDashboard_SearchView();
        $view->expectOnce('render');
        
        $criteria = array();
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => $criteria
        ));
        
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
        
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        
        $this->formElementFactory->setReturnValue('getProjectSharedFields', array());
        
        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
        
        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
        $this->search->expectOnce('getMatchingArtifacts', array(array(), $criteria));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView', 'getTrackers'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', array());
        
        $controller->search();
    }
    
    public function testNoValueSubmittedShouldNotSelectAnythingInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66'
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $criteria = $controller->getCriteria($project, $report);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array());
    }
    
    public function testSubmittedValueIsSelectedInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => array('220' => array('values' => array('350')))
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $criteria = $controller->getCriteria($project, $report);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350));
    }
    
    public function testSubmittedValuesAreSelectedInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => array('220' => array('values' => array('350', '351')),
                                '221' => array('values' => array('352')))
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build(),
                        aTextField()->withId(221)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $criteria = $controller->getCriteria($project, $report);
        $this->assertEqual(count($criteria), 2);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350, 351));
        $this->assertEqual($criteria[1]->field->getCriteriaValue($criteria[1]), array(352));
    }
    
    public function testImpactedTrackersShouldBeAvailableForSeachAndForViews() {
        $tracker = aTracker()->withId(110)->build();
        $fields = array(aTextField()->withId(220)->withTracker($tracker)->build(),
                        aTextField()->withId(221)->withTracker($tracker)->build());
        $this->formElementFactory->setReturnValue('getAllProjectSharedFields', $fields);
        
        $project = new MockProject();
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        $trackers   = $controller->getTrackers($project);
        $this->assertEqual(count($trackers), 1);
        
        $tracker1 = array_pop($trackers);
        $this->assertIsA($tracker1, 'Tracker');
        $this->assertEqual($tracker1->getId(), 110);
    }
    
    public function testSearchCallGetViewWithTrackerHierarchy() {
        $view = new MockAgileDashboard_SearchView();
        
        $trackers = array();
        $trackers_hierarchy = new Tracker_Hierarchy(array());
        
        $controller = TestHelper::getPartialMock(
            'AgileDashboard_SearchController', 
            array('getView', 'getTrackers', 'getProject', 'getService', 'getArtifacts', 'getReport', 'getCriteria', 'getTrackersHierarchy')
        );
        
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_dao);
        
        $controller->expectOnce('getView', array('*', '*', '*', '*', '*', $trackers, $trackers_hierarchy));
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', $trackers);
        $controller->setReturnValue('getTrackersHierarchy', $trackers_hierarchy , array($trackers));
        
        $controller->search();
    }
    
    public function testSearchCreateTrackerHierarchyFromDatabase() {
        $view = new MockAgileDashboard_SearchView();
                                                                            
        $trackers_ids = array(111, 112);
        $trackers = array();
        foreach( $trackers_ids as $tracker_id) {
            $tracker = new MockTracker();
            $tracker->setReturnValue('getId', $tracker_id);
            $trackers[] = $tracker;
        }
        
        $hierarchy_dao = new MockTracker_Hierarchy_Dao();
        $hierarchy_dao->expectOnce('searchTrackerHierarchy', array($trackers_ids));
        $hierarchy_dao->setReturnValue('searchTrackerHierarchy', TestHelper::arrayToDar(array('parent_id' => 111, 'child_id' => 112)));
        
        $controller = TestHelper::getPartialMock(
            'AgileDashboard_SearchController', 
            array('getView', 'getTrackers', 'getProject', 'getService', 'getArtifacts', 'getReport', 'getCriteria')
        );
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $hierarchy_dao);
        
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', $trackers);

        $controller->search();
    }
}
?>
