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

class ViewBuilderTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();
        
        $this->formElementFactory = mock('Tracker_FormElementFactory');
        $this->tracker_factory    = mock('TrackerFactory');
        $this->search             = mock('Tracker_CrossSearch_Search');
        $this->criteria_builder   = mock('Tracker_CrossSearch_CriteriaBuilder');
    }

    public function itBuildPlanningContentView() {
        $tracker_ids = array();
        $this->tracker_factory->setReturnValue('getTrackersByGroupIdUserCanView', $tracker_ids);
        
        $this->search->setReturnValue('getHierarchicallySortedArtifacts', new TreeNode());
        
        $this->criteria_builder->setReturnValue('getCriteria', array());
        
        $user    = aUser()->build();
        $project = new MockProject();
        
        $cross_search_criteria = aCrossSearchCriteria()->build();
        
        $this->search->expectOnce('getHierarchicallySortedArtifacts', array($user, $project, $tracker_ids, $cross_search_criteria, array()));
        
        $builder  = new Planning_ViewBuilder($this->formElementFactory, $this->search, $this->criteria_builder);
        $planning = aPlanning()->build();
        $view     = $builder->buildPlanningView($user, $project, $cross_search_criteria, array(), $tracker_ids, $planning, '');
        
        $this->assertIsA($view, 'Planning_SearchContentView');
    }
}

?>
