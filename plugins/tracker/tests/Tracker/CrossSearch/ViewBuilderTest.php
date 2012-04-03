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

    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
    }

    public function itBuildCustomContentView() {
        $formElementFactory = new MockTracker_FormElementFactory();
        $tracker_factory    = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackersByGroupId', array());
        $search             = new MockTracker_CrossSearch_Search();
        $search->setReturnValue('getHierarchicallySortedArtifacts', new TreeNode());
        $criteria_builder   = new MockTracker_CrossSearch_CriteriaBuilder();
        $criteria_builder->setReturnValue('getCriteria', array());
        $project            = new MockProject();
        
        $builder            = new Tracker_CrossSearch_ViewBuilder($formElementFactory, $tracker_factory, $search, $criteria_builder);
        $view               = $builder->buildContentView($project, aCrossSearchCriteria()->build());
        
        $this->assertIsA($view, 'Tracker_CrossSearch_SearchContentView');
    }
    
}

class Tracker_CrossSearch_ViewBuilder_BuildViewTest extends TuleapTestCase {
    public function itThrowsAnExceptionIfTheServiceTrackerIsntActivated() {
        $project = new MockProject();
        $builder = new Tracker_CrossSearch_ViewBuilder(new MockTracker_FormElementFactory(), new MockTrackerFactory(), new MockTracker_CrossSearch_Search(), new MockTracker_CrossSearch_CriteriaBuilder());
        
        $this->expectException('Tracker_CrossSearch_ServiceNotUsedException');
        $cross_search_criteria = aCrossSearchCriteria()
                                ->forOpenItems()
                                ->build();

        $builder->buildView($project, $cross_search_criteria);
    }
}


?>
