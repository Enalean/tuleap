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

class AgileDashboardRouter_RouteShowPlanningTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        
    }
    
    public function itRoutesToTheArtifactPlannificationByDefault() {        
        $router  = TestHelper::getPartialMock('AgileDashboardRouter', array('getViewBuilder', 'renderAction', 'getPlanningFactory', 'getArtifactFactory'));
        $router->__construct(mock('Plugin'));
        
        stub($router)->getViewBuilder()->returns(mock('Tracker_CrossSearch_ViewBuilder'));
        stub($router)->getPlanningFactory()->returns(mock('PlanningFactory'));
        stub($router)->getArtifactFactory()->returns(mock('Tracker_ArtifactFactory'));
        
        
        $request = new Codendi_Request(array(), array('REQUEST_URI' => 'someurl'));
        $request->setCurrentUser(aUser()->build());
        
        $router->expectOnce('renderAction', array(new IsAExpectation('Planning_ArtifactPlannificationController'), 'show', $request, '*'));
        $router->routeShowPlanning($request);
    }
    
    public function itRoutesToArtifactCreationWhenAidIsSetToMinusOne() {
        $router  = TestHelper::getPartialMock('AgileDashboardRouter', array('executeAction', 'getPlanningFactory'));
        $router->__construct(mock('Plugin'));
        stub($router)->getPlanningFactory()->returns(mock('PlanningFactory'));
        
        $request = new Codendi_Request(array('aid' => '-1'));
        
        $router->expectOnce('executeAction', array(new IsAExpectation('Planning_ArtifactCreationController'), 'createArtifact'));
        $router->routeShowPlanning($request);
    }
    
    public function itRoutesToTheArtifactPlannificationWhenTheAidIsSetToAPositiveNumber() {
        $router  = TestHelper::getPartialMock('AgileDashboardRouter', array('getViewBuilder', 'renderAction', 'getPlanningFactory', 'getArtifactFactory'));
        $router->__construct(mock('Plugin'));
        
        stub($router)->getViewBuilder()->returns(mock('Tracker_CrossSearch_ViewBuilder'));
        stub($router)->getPlanningFactory()->returns(mock('PlanningFactory'));
        stub($router)->getArtifactFactory()->returns(mock('Tracker_ArtifactFactory'));
        
        
        $request = new Codendi_Request(array('aid' => '732'),  array('REQUEST_URI' => 'someurl'));
        $request->setCurrentUser(aUser()->build());
        
        $router->expectOnce('renderAction', array(new IsAExpectation('Planning_ArtifactPlannificationController'), 'show', $request, '*'));
        $router->routeShowPlanning($request);
    }
}

?>
