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

require_once dirname(__FILE__) . '/../../Test_Tracker_Builder.php';
require_once dirname(__FILE__) . '/../../Test_Tracker_FormElement_Builder.php';

require_once dirname(__FILE__) . '/../../../include/Tracker/CrossSearch/SearchController.class.php';
require_once 'common/include/Codendi_Request.class.php';
require_once 'common/project/ProjectManager.class.php';
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');
Mock::generate('AgileDashboard_SearchView');
Mock::generate('AgileDashboard_Search');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('Tracker_Hierarchy');

class AgileDashboard_SearchControllerIndexTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->service            = new MockService();
        $this->project            = new MockProject();
        $this->manager            = new MockProjectManager();
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        $this->request            = new Codendi_Request(array('group_id' => '66'));
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->search             = new MockAgileDashboard_Search();
        $this->hierarchy_factory  = new MockTracker_HierarchyFactory();
    }
    
    public function testSearchRendersViewForServiceWithCriteria() {
        $view = new MockAgileDashboard_SearchView();
        $view->expectOnce('render');
                
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
        
        
        $fields = array(aTextField()->build(), aStringField()->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields, array($this->project));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView', 'getTrackers'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', array());
        
        $controller->search();
    }
    
    public function testSearchRedirectsWithErrorMessageIfServiceIsNotUsed() {
        $this->project->setReturnValue('getService', null, array('plugin_agiledashboard'));
        $this->project->setReturnValue('getUnixName', 'coin');

        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);

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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
        
        $controller->search();
    }
    
    public function testSearchActionRendersTheSearchView() {
        $view = new MockAgileDashboard_SearchView();
        $view->expectOnce('render');
        
        
        $tracker_hierarchy = new MockTracker_Hierarchy();
        $this->hierarchy_factory->setReturnValue('getHierarchy', $tracker_hierarchy);
        
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
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView', 'getTrackers'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
        $controller->setReturnValue('getView', $view);
        $controller->setReturnValue('getTrackers', array());
        
        $controller->search();
    }
    
    public function testSearchActionCallGetMatchingArtifactsWithAHierarchy() {
        $view = new MockAgileDashboard_SearchView();
        
        $tracker_hierarchy = new MockTracker_Hierarchy();
        $this->hierarchy_factory->setReturnValue('getHierarchy', $tracker_hierarchy);
        
        $criteria = array();
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => $criteria
        ));
        
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
        
        $this->formElementFactory->setReturnValue('getProjectSharedFields', array());
        
        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
        
        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
        $this->search->expectOnce('getMatchingArtifacts', array(array(), $tracker_hierarchy, $criteria));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView', 'getTrackers'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $this->hierarchy_factory);
        $trackers   = $controller->getTrackers($project);
        $this->assertEqual(count($trackers), 1);
        
        $tracker1 = array_pop($trackers);
        $this->assertIsA($tracker1, 'Tracker');
        $this->assertEqual($tracker1->getId(), 110);
    }
    
    public function testSearchCreateTrackerHierarchyFromDatabase() {
        $this->GivenAProjectThatUseTheService();
        
        $trackers_ids = array(111, 112);
        $this->GivenAListOfTrackerIds($trackers_ids);
        
        $hierarchy_factory = $this->GivenAHierarchyFactory();
        $hierarchy_factory->expectOnce('getHierarchy', array($trackers_ids));
        
        $controller = $this->GivenAControllerWithAHierarchyFactory($hierarchy_factory);
        $controller->search();
    }
    
    private function GivenAListOfTrackerIds($trackers_ids) {
        $trackers = array();
        $fields   = array();
        foreach( $trackers_ids as $tracker_id) {
            $tracker = new MockTracker();
            $tracker->setReturnValue('getId', $tracker_id);
            $fields[] = aTextField()->withId($tracker_id * 10)->withTracker($tracker)->build();
        }
        $this->formElementFactory->setReturnValue('getAllProjectSharedFields', $fields);
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
    }
    
    private function GivenAControllerWithAHierarchyFactory($hierarchy_factory) {
        $view = new MockAgileDashboard_SearchView();
        $controller = TestHelper::getPartialMock(
            'AgileDashboard_SearchController', 
            array('getView')
        );
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search, $hierarchy_factory);
        
        $controller->setReturnValue('getView', $view);
        return $controller;
    }
    
    private function GivenAHierarchyFactory() {
        $trackers_hierarchy = new Tracker_Hierarchy(array());
        
        $hierarchy_factory = new MockTracker_HierarchyFactory();
        $hierarchy_factory->setReturnValue('getHierarchy', $trackers_hierarchy);
        return $hierarchy_factory;
    }
    
    private function GivenAProjectThatUseTheService() {
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
    }
}
?>
