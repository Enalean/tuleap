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
require_once 'Test_CriteriaBuilder.php';

Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('Service');
Mock::generate('Tracker_CrossSearch_SearchView');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_ViewBuilder');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_HierarchyFactory');
Mock::generate('Tracker_Hierarchy');
Mock::generate('Tracker');
Mock::generate('TrackerFactory');
Mock::generate('Tracker_CrossSearch_Criteria');

class Tracker_CrossSearch_SearchControllerIndexTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();

        $this->service               = new MockService();
        $this->project               = new MockProject();
        $this->manager               = new MockProjectManager();
        $criteria                    = array('124' => array('stuff'));
        $empty_title                 = 'toto';
        $semantic_criteria           = array('title' => $empty_title, 'status' => 'Closed');
        $this->cross_search_criteria = aCrossSearchCriteria()
                ->withSharedFieldsCriteria($criteria)
                ->withSemanticCriteria($semantic_criteria)
                ->build();
        $this->request               = new Codendi_Request(array('group_id' => '66',
                                                                 'criteria' => $criteria,
                                                                 'semantic_criteria' => $semantic_criteria));
        $this->view_builder          = new MockTracker_CrossSearch_ViewBuilder();
        
        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        $this->project->setReturnValue('getGroupId', '123');
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

    public function itRedirectsWithErrorMessageIfServiceIsNotUsed() {
        $this->project->setReturnValue('getService', null, array('plugin_tracker'));
        $this->project->setReturnValue('getUnixName', 'coin');

        $this->view_builder->throwOn('buildView', new Tracker_CrossSearch_ServiceNotUsedException());
        $controller = $this->getController();

        $GLOBALS['HTML']->expectOnce('addFeedback', array('error', '*'));
        $GLOBALS['HTML']->expectOnce('redirect', array('/projects/coin/'));

        $controller->search();
    }
        
    public function itRendersViewUsingTheGivenProjectAndCriteria() {
        $view = new MockTracker_CrossSearch_SearchView();
        $view->expectOnce('render');
                
        $controller = $this->getController();        
        $this->view_builder->setReturnValue('buildView', $view);
        $this->view_builder->expectOnce('buildView', array($this->project, $this->cross_search_criteria));
        
        $controller->search();
    }
    
    public function itAssumesNoCriteriaIfThereIsNoneInTheRequest() {
        $no_criteria = aCrossSearchCriteria()->build();
        $this->view_builder = new MockTracker_CrossSearch_ViewBuilder();
        $this->view_builder->expectOnce('buildView', array('*', $no_criteria));
        $this->view_builder->setReturnValue('buildView', new MockTracker_CrossSearch_SearchView());
        $this->request = new Codendi_Request(array(
            'group_id' => '66',
        ));

        $this->manager->setReturnValue('getProject', $this->project, array('66'));
        $controller = $this->getController();
        $controller->search();
    }

    private function getController() {
        return new Tracker_CrossSearch_SearchController($this->request, $this->manager, $GLOBALS['HTML'], $this->view_builder);
    }
}

?>
