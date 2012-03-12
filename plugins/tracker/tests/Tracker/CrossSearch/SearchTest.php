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
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/Search.class.php';
Mock::generate('AgileDashboard_SharedFieldFactory');
Mock::generate('AgileDashboard_SearchDao');
Mock::generate('Project');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Hierarchy');

class AgileDashboard_SearchTest extends TuleapTestCase {
    
    function setUp() {
        parent::setUp();
        $this->project            = new MockProject();
        $this->sharedFieldFactory = new MockAgileDashboard_SharedFieldFactory();
        $this->searchDao          = new MockAgileDashboard_SearchDao();
        $this->trackerIds         = array(201, 202);
        $this->trackers           = array(aTracker()->withId(201)->build(), aTracker()->withId(202)->build());
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao);
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
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao);
        $artifacts = $this->search->getMatchingArtifacts(array(), $tracker_hierarchy, $criteria);
        
        $this->assertFalse($artifacts->hasChildren());
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
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao);
        
        
        $artifacts = $this->search->getMatchingArtifacts($trackers, $tracker_hierarchy);
        $expected  = $this->getExpectedForTrackerOutsideHierarchy();
        $this->assertEqual($artifacts->__toString(), $expected->__toString());
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
            array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '66',)
        );
    }
    
    private function getExpectedForTrackerOutsideHierarchy() {
        $root = new TreeNode();
        $root->setId(0);
        
        $node_7 = new TreeNode();
        $node_7->setId(7);
        $node_7->setData(array('id' => 7, 'tracker_id' => 112, 'artifactlinks' => '5'));
        $root->addChild($node_7);
        
        $node_5 = new TreeNode();
        $node_5->setId(5);
        $node_5->setData(array('id' => 5, 'tracker_id' => 111, 'artifactlinks' => ''));
        $node_7->addChild($node_5);
        
        $node_6 = new TreeNode();
        $node_6->setId(6);
        $node_6->setData(array('id' => 6, 'tracker_id' => 112, 'artifactlinks' => '8'));
        $root->addChild($node_6);

        $node_8 = new TreeNode();
        $node_8->setId(8);
        $node_8->setData(array('id' => 8, 'tracker_id' => 111, 'artifactlinks' => '11,9,34'));
        $node_6->addChild($node_8);
        
        $node_11 = new TreeNode();
        $node_11->setId(11);
        $node_11->setData(array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => ''));
        $node_8->addChild($node_11);
        
        $node_9 = new TreeNode();
        $node_9->setId(9);
        $node_9->setData(array('id' => 9, 'tracker_id' => 93, 'artifactlinks' => ''));
        $node_8->addChild($node_9);
        
        $node_10 = new TreeNode();
        $node_10->setId(10);
        $node_10->setData(array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '66'));
        $root->addChild($node_10);
        
        $node_66 = new TreeNode();
        $node_66->setId(66);
        $node_66->setData(array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => ''));
        $root->addChild($node_66);
        return $root;
    }
    
    private function GivenATrackerHierarchy() {
        $hierarchy = new Tracker_Hierarchy();
        $hierarchy->addRelationship(112, 111);
        $hierarchy->addRelationship(111, 113);
        $hierarchy->addRelationship(201, 202);
        return $hierarchy;
    }
   
}
?>
