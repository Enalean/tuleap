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
require_once dirname(__FILE__) . '/../../../include/Tracker/CrossSearch/ViewBuilder.class.php';
require_once dirname(__FILE__) . '/../../../include/Tracker/TrackerFactory.class.php';

require_once 'common/include/Codendi_Request.class.php';
require_once 'common/project/ProjectManager.class.php';

Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');
Mock::generate('Tracker_CrossSearch_SearchView');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_ViewBuilder');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('Tracker_Hierarchy');
Mock::generate('Tracker');
Mock::generate('TrackerFactory');

class Tracker_CrossSearch_SearchControllerIndexTest extends TuleapTestCase {
    public function itCreatesTrackerHierarchyFromDatabase() {
        $this->GivenAProjectThatUseTheService();
        
        $trackers_ids = array(111, 112);
        $this->GivenAListOfTrackerIds($trackers_ids);
        
        $hierarchy_factory = $this->GivenAHierarchyFactory();
        $hierarchy_factory->expectOnce('getHierarchy', array($trackers_ids));
        
        $controller = $this->GivenAControllerWithAHierarchyFactory($hierarchy_factory);
        $controller->search();
    }
    
    public function itCallsGetMatchingArtifactsWithAHierarchy() {
        $view = new MockTracker_CrossSearch_SearchView();
        
        $tracker_hierarchy = new MockTracker_Hierarchy();
        $this->hierarchy_factory->setReturnValue('getHierarchy', $tracker_hierarchy);
        
        $criteria = array();
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => $criteria
        ));
        
        $this->project->setReturnValue('getService', $this->service, array('plugin_tracker'));
        
        $this->formElementFactory->setReturnValue('getProjectSharedFields', array());
        
        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
        
        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
        $this->search->expectOnce('getMatchingArtifacts', array(array(), $tracker_hierarchy, $criteria));
        
        $controller = $this->getController();
        $this->view_builder->setReturnValue('getView', $view);
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', array());
        
        $controller->search();
    }
        
    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->tracker_factory    = new MockTrackerFactory();
        $view_builder = TestHelper::getPartialMock('Tracker_CrossSearch_ViewBuilder', array('getView'));
        $view_builder->__construct($this->formElementFactory, $this->tracker_factory);
        
        $this->service            = new MockService();
        $this->project            = new MockProject();
        $this->manager            = new MockProjectManager();
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        $this->request            = new Codendi_Request(array('group_id' => '66', 'criteria' => array()));
        $this->search             = new MockTracker_CrossSearch_Search();
        $this->search->setReturnValue('getMatchingArtifacts', new TreeNode());
        $this->hierarchy_factory  = new MockTracker_HierarchyFactory();
        $this->view_builder       = $view_builder;
        
        $this->project->setReturnValue('getGroupId', '123');
    }

    public function itRedirectsWithErrorMessageIfServiceIsNotUsed() {
        $this->project->setReturnValue('getService', null, array('plugin_tracker'));
        $this->project->setReturnValue('getUnixName', 'coin');

        $controller = $this->getController();

        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/projects/coin/'));

        $controller->search();
    }
    
    public function itRedirectsToHomepageWhenProjectDoesNotExist() {
        $this->project->setReturnValue('isError', true);
        
        $this->manager->setReturnValue('getProject', $this->project, array('invalid_project_id'));
        
        $this->request = new Codendi_Request(array('group_id' => 'invalid_project_id'));
        
        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/'));
        
        $controller = $this->getController();
        
        $controller->search();
    }
        
    public function itRendersTheSearchView() {
        $view = new MockTracker_CrossSearch_SearchView();
        $view->expectOnce('render');
        
        
        $tracker_hierarchy = new MockTracker_Hierarchy();
        $this->hierarchy_factory->setReturnValue('getHierarchy', $tracker_hierarchy);
        
        $criteria = array();
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => $criteria
        ));
        
        $this->project->setReturnValue('getService', $this->service, array('plugin_tracker'));
        
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        
        $noFields = array();
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $noFields);
        
        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
        
        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
        

        $controller = $this->getController();
        $this->view_builder->setReturnValue('getView', $view);
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', array());
        
        $controller->search();
    }
    
    public function itRendersViewForServiceWithCriteria() {
        $view = new MockTracker_CrossSearch_SearchView();
        $view->expectOnce('render');
                
        $this->project->setReturnValue('getService', $this->service, array('plugin_tracker'));
        
        
        $fields = array(aTextField()->build(), aStringField()->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields, array($this->project));

        $controller = $this->getController();        
        $this->view_builder->setReturnValue('getView', $view);
        $this->tracker_factory->setReturnValue('getTrackersByGroupId', array());
        
        $controller->search();
    }
    
    public function itAssumesNoCriteriaIfThereIsNoneInTheRequest() {
        $this->view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $this->view_builder->expectOnce('buildView', array('*', array()));
        $this->view_builder->setReturnValue('buildView', new MockTracker_CrossSearch_SearchView());
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
        ));

        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        $controller = $this->getController();
        $controller->search();
    }

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
        $controller = $this->getControllerWithHierarchyFactory($hierarchy_factory);

        $this->view_builder->setReturnValue('getView', $view);
        
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

    private function getController() {
        return $this->getControllerWithHierarchyFactory($this->hierarchy_factory);
    }

    private function getControllerWithHierarchyFactory($hierarchy_factory) {
        return new Tracker_CrossSearch_SearchController($this->request, $this->manager, $GLOBALS['HTML'], $this->search, $hierarchy_factory, $this->view_builder);
    }

}

?>
