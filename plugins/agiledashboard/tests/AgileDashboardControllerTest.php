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

require_once dirname(__FILE__) . '/../../tracker/tests/Test_Tracker_FormElement_Builder.php';

require_once dirname(__FILE__) . '/../include/AgileDashboard/SearchController.class.php';
require_once 'common/include/Codendi_Request.class.php';
require_once 'common/project/ProjectManager.class.php';
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');
Mock::generate('AgileDashboard_SearchView');
Mock::generate('AgileDashboard_Search');
Mock::generate('Tracker_FormElementFactory');

class AgileDashboardControllerIndexTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->service = new MockService();
        $this->project = new MockProject();
        $this->manager = new MockProjectManager();
        $this->request = new Codendi_Request(array('group_id' => '66'));
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->search = new MockAgileDashboard_Search();
    }
    
    function testIndexActionRendersViewForServiceWithCriteria() {
        $view = new MockAgileDashboard_SearchView();
        $view->expectOnce('render');
                
        $this->project->setReturnValue('getService', $this->service, array('plugin_agiledashboard'));
        
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        
        $fields = array(aTextField()->build(), aStringField()->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields, array($this->project));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search);
        $controller->setReturnValue('getView', $view);
        
        $controller->search();
    }
    
    public function testSearchRedirectsWithErrorMessageIfServiceIsNotUsed() {
        $this->project->setReturnValue('getService', null, array('plugin_agiledashboard'));
        $this->project->setReturnValue('getUnixName', 'coin');

        $this->manager->setReturnValue('getProject', $this->project, array('66'));

        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search);

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
        
        $controller = new AgileDashboard_SearchController($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search);
        
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
        
        //$fields = array(aTextField()->build(), aStringField()->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', array());
        
        $matchingIds = array(array('artifactId1', 'artifactId2'), array('changesetId1', 'changesetId2'));
        
        $this->search->setReturnValue('getMatchingArtifacts', $matchingIds);
        $this->search->expectOnce('getMatchingArtifacts', array($criteria));
        
        $controller = TestHelper::getPartialMock('AgileDashboard_SearchController', array('getView'));
        $controller->__construct($this->request, $this->manager, $this->formElementFactory, $GLOBALS['Language'], $GLOBALS['HTML'], $this->search);
        $controller->setReturnValue('getView', $view);
        
        $controller->search();
    }
}
?>
