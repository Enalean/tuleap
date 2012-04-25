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
require_once dirname(__FILE__) . '/../../../include/Tracker/CrossSearch/SemanticValueFactory.class.php';
require_once dirname(__FILE__) . '/../../../include/Tracker/TrackerFactory.class.php';
require_once 'common/include/Codendi_Request.class.php';
require_once 'Test_CriteriaBuilder.php';
require_once dirname(__FILE__) . '/../../../include/Tracker/CrossSearch/SemanticStatusReportField.class.php';

Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_CrossSearch_Search');
Mock::generate('Tracker_CrossSearch_SearchContentView');
Mock::generate('TrackerFactory');
Mock::generate('Project');
Mock::generate('Tracker_Report');
Mock::generate('Tracker_CrossSearch_SemanticValueFactory');
Mock::generate('Tracker_CrossSearch_CriteriaBuilder');

class Fake_Tracker_CrossSearch_SearchContentView extends Tracker_CrossSearch_SearchContentView {
}

class Tracker_CrossSearch_ViewBuilderTest extends TuleapTestCase {

    public function itBuildCustomContentView() {
        $formElementFactory = new MockTracker_FormElementFactory();
        $tracker_factory    = new MockTrackerFactory();
        $tracker_ids        = array();
        $tracker_factory->setReturnValue('getTrackerByGroupIdUserCanView', $tracker_ids);
        $search             = new MockTracker_CrossSearch_Search();
        $search->setReturnValue('getHierarchicallySortedArtifacts', new TreeNode());
        $criteria_builder   = new MockTracker_CrossSearch_CriteriaBuilder();
        $criteria_builder->setReturnValue('getCriteria', array());
        $user               = aUser()->build();
        $project            = new MockProject();
        
        $cross_search_criteria = aCrossSearchCriteria()->build();
        
        $search->expectOnce('getHierarchicallySortedArtifacts', array($user, $project, $tracker_ids, $cross_search_criteria, array()));
        
        $builder            = new Tracker_CrossSearch_ViewBuilder($formElementFactory, $tracker_factory, $search, $criteria_builder);
        $content_view_class = 'Tracker_CrossSearch_SearchContentView';
        $view               = $builder->buildCustomContentView($content_view_class, $user, $project, $cross_search_criteria, array(), $tracker_ids);
        
        $this->assertIsA($view, $content_view_class);
    }
}

class Tracker_CrossSearch_ViewBuilder_BuildViewTest extends TuleapTestCase {
    public function itThrowsAnExceptionIfTheServiceTrackerIsntActivated() {
        $user    = aUser()->build();
        $project = new MockProject();
        $builder = new Tracker_CrossSearch_ViewBuilder(new MockTracker_FormElementFactory(), new MockTrackerFactory(), new MockTracker_CrossSearch_Search(), new MockTracker_CrossSearch_CriteriaBuilder());
        
        $this->expectException('Tracker_CrossSearch_ServiceNotUsedException');
        $cross_search_criteria = aCrossSearchCriteria()
                                ->forOpenItems()
                                ->build();

        $builder->buildView($user, $project, $cross_search_criteria);
    }
    
    public function _itReturnsCrossSearchViewIncludingTheContentView() {
        $user               = aUser()->build();
        $project            = mock('Project');
        $cross_search_query = mock('Tracker_CrossSearch_Query');
        
        $view_builder = TestHelper::getPartialMock('Tracker_CrossSearch_ViewBuilder', array('buildContentView', 'getService'));
        //var_dump($view_builder);
        //stub($view_builder)->getService()->returns(true);
        $view_builder->expectOnce('buildContentView', array($user, $project, $cross_search_query));
        $view_builder->buildView($user, $project, $cross_search_query);
    }
}


?>
