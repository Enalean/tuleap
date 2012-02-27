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

require_once dirname(__FILE__) . '/../../../tracker/tests/Test_Tracker_Builder.php';
require_once dirname(__FILE__) . '/../../../tracker/tests/Test_Tracker_FormElement_Builder.php';
require_once dirname(__FILE__) .'/../../include/AgileDashboard/Search.class.php';
Mock::generate('AgileDashboard_SharedFieldFactory');
Mock::generate('AgileDashboard_SearchDao');
Mock::generate('Project');
Mock::generate('Tracker_FormElementFactory');

class AgileDashboard_SearchTest extends UnitTestCase {
    
    function setUp() {
        $this->project            = new MockProject();
        $this->sharedFieldFactory = new MockAgileDashboard_SharedFieldFactory();
        $this->searchDao          = new MockAgileDashboard_SearchDao();
        $this->trackerIds         = array(201, 202);
        $this->trackers           = array(aTracker()->withId(201)->build(), aTracker()->withId(202)->build());
        
        $this->search = new AgileDashboard_Search($this->sharedFieldFactory, $this->searchDao);
    }
    
    function testGetMatchingArtifactsDelegatesToSharedFieldFactoryAndSearchDao() {
        $criteria  = array('220' => array('values' => array('350')));
                
        $sharedFields = array(new AgileDashboard_SharedField());
        
        $this->sharedFieldFactory->expectOnce('getSharedFields', array($criteria));
        $this->sharedFieldFactory->setReturnValue('getSharedFields', $sharedFields);
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->trackerIds, $sharedFields));
        
        $this->search->getMatchingArtifacts($this->trackers, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoCriteria() {
        $criteria  = array('220' => array('values' => array('')));

        $this->searchDao->expectOnce('searchArtifactsFromTrackers', array($this->trackerIds));

        $this->search->getMatchingArtifacts($this->trackers, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoArtifactsAndNoTrackers() {
        $criteria   = array('220' => array('values' => array('')));
                
        $this->searchDao->expectNever('searchArtifactsFromTrackers');
        
        $this->search = new AgileDashboard_Search($this->sharedFieldFactory, $this->searchDao);
        $artifacts = $this->search->getMatchingArtifacts(array(), $criteria);
        
        $this->assertEqual(count($artifacts), 0);
    }
}
?>
