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

require_once dirname(__FILE__).'/../include/AgileDashboardRouter.class.php';
require_once dirname(__FILE__).'/../../../tests/simpletest/common/include/builders/aRequest.php';

class AgileDashboardRouter_RouteShowPlanningTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        
        $this->router = TestHelper::getPartialMock('AgileDashboardRouter',
                                             array('getViewBuilder',
                                                   'renderAction',
                                                   'executeAction',
                                                   'getPlanningFactory',
                                                   'getProjectManager',
                                                   'getArtifactFactory',
                                                   'getMilestoneFactory'));
        $this->router->__construct(mock('Plugin'));
        
        stub($this->router)->getViewBuilder()->returns(mock('Tracker_CrossSearch_ViewBuilder'));
        stub($this->router)->getProjectManager()->returns(mock('ProjectManager'));
        stub($this->router)->getMilestoneFactory()->returns(mock('Planning_MilestoneFactory'));
    }
    
    public function itRoutesToTheArtifactPlannificationByDefault() {
        $request = aRequest()->withUri('someurl')->build();
        $this->router->expectOnce('renderAction', array(new IsAExpectation('Planning_MilestoneController'), 'show', $request, '*'));
        $this->router->routeShowPlanning($request);
    }
    
    public function itRoutesToTheArtifactPlannificationWhenTheAidIsSetToAPositiveNumber() {
        $request = aRequest()->with('aid', '732')->withUri('someurl')->build();
        $this->router->expectOnce('renderAction', array(new IsAExpectation('Planning_MilestoneController'), 'show', $request, '*'));
        $this->router->routeShowPlanning($request);
    }

    public function itRoutesToArtifactCreationWhenAidIsSetToMinusOne() {
        stub($this->router)->getPlanningFactory()->returns(mock('PlanningFactory'));
        $request = new Codendi_Request(array('aid' => '-1'));
        $this->router->expectOnce('executeAction', array(new IsAExpectation('Planning_ArtifactCreationController'), 'createArtifact'));
        $this->router->routeShowPlanning($request);
    }
    
}

?>
