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
Mock::generate('Tracker_Hierarchy');

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
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = array('220' => array('values' => array('350')));
                
        $sharedFields = array(new AgileDashboard_SharedField());
        
        $this->sharedFieldFactory->expectOnce('getSharedFields', array($criteria));
        $this->sharedFieldFactory->setReturnValue('getSharedFields', $sharedFields);
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->trackerIds, $sharedFields));
        
        $this->search->getMatchingArtifacts($this->trackers, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoCriteria() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = array('220' => array('values' => array('')));

        $this->searchDao->expectOnce('searchArtifactsFromTrackers', array($this->trackerIds));

        $this->search->getMatchingArtifacts($this->trackers, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoArtifactsAndNoTrackers() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria   = array('220' => array('values' => array('')));
                
        $this->searchDao->expectNever('searchArtifactsFromTrackers');
        
        $this->search = new AgileDashboard_Search($this->sharedFieldFactory, $this->searchDao);
        $artifacts = $this->search->getMatchingArtifacts(array(), $tracker_hierarchy, $criteria);
        
        $this->assertEqual(count($artifacts), 0);
    }
    
    function testGetMatchingArtifactsShouldOrderResultsAccordinglyToAOneLevelHierarchy() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $this->searchDao->setReturnValue('searchArtifactsFromTrackers', $this->getResultsWithOneLevel());
        $trackers = array(
            aTracker()->withId(111)->build(),
            aTracker()->withId(112)->build(),
        );
        $this->search = new AgileDashboard_Search($this->sharedFieldFactory, $this->searchDao);
        
        
        $artifacts = $this->search->getMatchingArtifacts($trackers, $tracker_hierarchy);
        $expected  = $this->getExpectedWithOneLevel();
        $this->assertEqual($artifacts, $expected);
    }
    
    private function getResultsWithOneLevel() {
        return TestHelper::arrayToDar(
            array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5',),
            array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8',),
            array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => '',)
        );
    }
    
    private function getExpectedWithOneLevel() {
        return array(
            array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5', 'level' => 0),
            array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => '', 'level' => 1),
            array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8', 'level' => 0),
        );
    }
    
    function testGetMatchingArtifactsShouldReturnArtifactFromTrackersOutsidesHierarchy() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $sharedFields      = array(new AgileDashboard_SharedField());
        
        $this->sharedFieldFactory->setReturnValue('getSharedFields', $sharedFields);
        
        $this->searchDao->setReturnValue('searchMatchingArtifacts', $this->getResultsForTrackerOutsideHierarchy());
        $trackers = array(
            aTracker()->withId(111)->build(),
            aTracker()->withId(112)->build(),
            aTracker()->withId(113)->build(),
            aTracker()->withId(666)->build(),
        );
        $this->search = new AgileDashboard_Search($this->sharedFieldFactory, $this->searchDao);
        
        
        $artifacts = $this->search->getMatchingArtifacts($trackers, $tracker_hierarchy);
        $expected  = $this->getExpectedForTrackerOutsideHierarchy();
        $this->assertEqual($artifacts, $expected);
    }
    
    private function getResultsForTrackerOutsideHierarchy() {
        return TestHelper::arrayToDar(
            array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => '',),
            array('id' => 8, 'tracker_id' => 111, 'artifactlinks' => '11,9,34',),
            array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => '',),
            array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5',),
            array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8',),
            array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => '',),
            array('id' => 9, 'tracker_id' => 113, 'artifactlinks' => '',),
            array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '42',)
        );
    }
    
    private function getExpectedForTrackerOutsideHierarchy() {
        return array(
            array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5',       'level' => 0),
            array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => '',        'level' => 1),
            array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8',       'level' => 0),
            array('id' => 8, 'tracker_id' => 111, 'artifactlinks' => '11,9,34', 'level' => 1),
            array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => '',       'level' => 2),
            array('id' => 9, 'tracker_id' => 113, 'artifactlinks' => '',        'level' => 2),
            array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '42',     'level' => 0),
            array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => '',       'level' => 0)
        );
    }
    
    private function GivenATrackerHierarchy() {
        $hierarchy = new MockTracker_Hierarchy();
        $hierarchy->setReturnValue('getLevel', 0, array(112));
        $hierarchy->setReturnValue('getLevel', 1, array(111));
        $hierarchy->setReturnValue('getLevel', 2, array(113));
        $hierarchy->setReturnValue('getLevel', 0, array(201));
        $hierarchy->setReturnValue('getLevel', 1, array(202));
        
        $hierarchy->throwOn('getLevel', new Tracker_Hierarchy_NotInHierarchyException(), array(666));
        
        return $hierarchy;
    }
   
}
?>
