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

require_once dirname(__FILE__) . '/../../Test_Tracker_FormElement_Builder.php';
require_once dirname(__FILE__) . '/../../../include/Tracker/CrossSearch/ViewBuilder.class.php';
require_once dirname(__FILE__) . '/../../../include/Tracker/TrackerFactory.class.php';
require_once 'common/include/Codendi_Request.class.php';

Mock::generate('Tracker_FormElementFactory');
Mock::generate('Project');
Mock::generate('Tracker_Report');

class Tracker_CrossSearch_ViewBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
    }
    
    public function testNoValueSubmittedShouldNotSelectAnythingInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66',
            'criteria' => array()
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getCriteria($project, $report);
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
        
        $criteria = $this->getCriteria($project, $report);
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
        
        $criteria = $this->getCriteria($project, $report);
        $this->assertEqual(count($criteria), 2);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350, 351));
        $this->assertEqual($criteria[1]->field->getCriteriaValue($criteria[1]), array(352));
    }
    
    private function getCriteria($project, $report) {
        $searchViewBuilder = new Tracker_CrossSearch_ViewBuilder($this->formElementFactory, new MockTrackerFactory());
        return $searchViewBuilder->getCriteria($project, $report, $this->request->get('criteria'));
    }
    
//            $view_builder = TestHelper::getPartialMock('Tracker_CrossSearch_ViewBuilder', array('getView'));
//        $view_builder->__construct($this->formElementFactory, $this->tracker_factory);

    
//    public function itCreatesTrackerHierarchyFromDatabase() {
//        $this->GivenAProjectThatUseTheService();
//        
//        $trackers_ids = array(111, 112);
//        $this->GivenAListOfTrackerIds($trackers_ids);
//        
//        $hierarchy_factory = $this->GivenAHierarchyFactory();
//        $hierarchy_factory->expectOnce('getHierarchy', array($trackers_ids));
//        
//        $controller = $this->GivenAControllerWithAHierarchyFactory($hierarchy_factory);
//        $controller->search();
//    }
//    
//    public function itCallsGetMatchingArtifactsWithAHierarchy() {
//        $view = new MockTracker_CrossSearch_SearchView();
//        
//        $tracker_hierarchy = new MockTracker_Hierarchy();
//        $this->hierarchy_factory->setReturnValue('getHierarchy', $tracker_hierarchy);
//        
//        $criteria = array();
//        $this->request = new Codendi_Request(array(
//            'group_id' => '66', 
//            'criteria' => $criteria
//        ));
//        
//        $this->project->setReturnValue('getService', $this->service, array('plugin_tracker'));
//        
//        $this->formElementFactory->setReturnValue('getProjectSharedFields', array());
//        
//        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
//        
//        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
//        $this->search->expectOnce('getMatchingArtifacts', array(array(), $tracker_hierarchy, $criteria));
//        
//        $controller = $this->getController();
//        $this->view_builder->setReturnValue('getView', $view);
//        $this->tracker_factory->setReturnValue('getTrackersByGroupId', array());
//        
//        $controller->search();
//    }

    private function GivenAListOfTrackerIds($trackers_ids) {
        $trackers = array();
        $fields   = array();
        foreach( $trackers_ids as $tracker_id) {
            $tracker = new MockTracker();
            $tracker->setReturnValue('getId', $tracker_id);
            $fields[] = aTextField()->withId($tracker_id * 10)->withTracker($tracker)->build();
            $trackers[] = $tracker;
        }
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', $trackers);
        $this->formElementFactory->setReturnValue('getAllProjectSharedFields', $fields);
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
    }
    
    private function GivenAControllerWithAHierarchyFactory($hierarchy_factory) {
        $view = new MockTracker_CrossSearch_SearchView();
        $this->view_builder->setReturnValue('buildView', $view);
        $controller = $this->getControllerWithHierarchyFactory($hierarchy_factory);

        
        return $controller;
    }
    
    private function GivenAHierarchyFactory() {
        $trackers_hierarchy = new Tracker_Hierarchy(array());
        
        $hierarchy_factory = new MockTracker_HierarchyFactory();
        $hierarchy_factory->setReturnValue('getHierarchy', $trackers_hierarchy);
        return $hierarchy_factory;
    }
    
    private function GivenAProjectThatUseTheService() {
        $this->project->setReturnValue('getService', $this->service, array('plugin_tracker'));
    }
    
    
    
}
?>
