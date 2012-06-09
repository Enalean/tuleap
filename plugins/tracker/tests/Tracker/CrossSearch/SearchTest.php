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

require_once dirname(__FILE__) . '/../../builders/aTracker.php';
require_once dirname(__FILE__) . '/../../builders/aField.php';
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/Search.class.php';
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/SemanticStatusReportField.class.php';
require_once dirname(__FILE__) .'/../../../include/Tracker/CrossSearch/Query.class.php';
require_once dirname(__FILE__).'/../../builders/aCrossSearchCriteria.php';

Mock::generate('Tracker_CrossSearch_SharedFieldFactory');
Mock::generate('Tracker_CrossSearch_SearchDao');
Mock::generate('Project');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Hierarchy');
Mock::generate('Tracker_HierarchyFactory');

class Tracker_CrossSearch_SearchTest extends TuleapTestCase {
    
    function setUp() {
        parent::setUp();
        $this->project_id              = 42;
        $this->project                 = stub('Project')->getId()->returns($this->project_id);
        $this->user                    = aUser()->build();
        $this->sharedFieldFactory      = new MockTracker_CrossSearch_SharedFieldFactory();
        $this->searchDao               = new MockTracker_CrossSearch_SearchDao();
        $this->trackerIds              = array(201, 202);
        $this->hierarchy_factory       = new MockTracker_HierarchyFactory();
        $this->artifact_link_field_ids = array();
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao, $this->hierarchy_factory, $this->artifact_link_field_ids);
    }
    
    function testGetMatchingArtifactsDelegatesToSharedFieldFactoryAndSearchDao() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $semantic_fields   = array('title'  => 'Foo',
                                   'status' => Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN);
        $criteria  = aCrossSearchCriteria()
                ->withSharedFieldsCriteria(array('220' => array('values' => array('350'))))
                ->withSemanticCriteria($semantic_fields)
                ->build();
                
        $sharedFields = array(new Tracker_CrossSearch_SharedField());
        $this->sharedFieldFactory->expectOnce('getSharedFields', array($criteria->getSharedFields()));
        $this->sharedFieldFactory->setReturnValue('getSharedFields', $sharedFields);
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, $criteria, $this->trackerIds, $sharedFields, $semantic_fields, $this->artifact_link_field_ids, array()));
        
        $this->search->getMatchingArtifacts($this->user, $this->project, $this->trackerIds, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoCriteria() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = aCrossSearchCriteria()
                ->withSharedFieldsCriteria(array('220' => array('values' => array(''))))
                ->build();
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, $criteria, $this->trackerIds, null, array('title' => '', 'status' => 'open'), $this->artifact_link_field_ids, array()));

        $this->search->getMatchingArtifacts($this->user, $this->project, $this->trackerIds, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoArtifactsAndNoTrackers() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = aCrossSearchCriteria()
                ->withSharedFieldsCriteria(array('220' => array('values' => array(''))))
                ->build();
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, $criteria, array(), null, array('title' => '', 'status' => 'open'), $this->artifact_link_field_ids, array()));
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao, $this->hierarchy_factory, $this->artifact_link_field_ids);
        $artifacts = $this->search->getMatchingArtifacts($this->user, $this->project, array(), $tracker_hierarchy, $criteria);
        
        $this->assertFalse($artifacts->hasChildren());
    }
    
    public function itPassesTheCriteriaToTheDao() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        
        $query  = aCrossSearchCriteria()
                ->withArtifactIds(array(200 => array(4, 6)))
                ->build();
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, new EqualExpectation($query), '*', '*', '*', '*', '*'));
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao, $this->hierarchy_factory, $this->artifact_link_field_ids);
        $artifacts = $this->search->getMatchingArtifacts($this->user, $this->project, array(), $tracker_hierarchy, $query);
    }
    
    function testGetMatchingArtifactsShouldReturnArtifactFromTrackersOutsidesHierarchy() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $sharedFields      = array(new Tracker_CrossSearch_SharedField());
        
        $this->sharedFieldFactory->setReturnValue('getSharedFields', $sharedFields);
        
        $this->searchDao->setReturnValue('searchMatchingArtifacts', $this->getResultsForTrackerOutsideHierarchy());
        $trackerIds = array(111, 112, 113, 666);
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao, $this->hierarchy_factory, $this->artifact_link_field_ids);
        
        $criteria  = aCrossSearchCriteria()->build();
        
        $artifacts = $this->search->getMatchingArtifacts($this->user, $this->project, $trackerIds, $tracker_hierarchy, $criteria);
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
        $root    = new TreeNode(null, 0);
        $node_7  = new TreeNode(array('id' => 7,  'tracker_id' => 112, 'artifactlinks' => '5'), 7);
        $node_5  = new TreeNode(array('id' => 5,  'tracker_id' => 111, 'artifactlinks' => ''), 5);
        $node_6  = new TreeNode(array('id' => 6,  'tracker_id' => 112, 'artifactlinks' => '8'), 6);
        $node_8  = new TreeNode(array('id' => 8,  'tracker_id' => 111, 'artifactlinks' => '11,9,34'), 8);
        $node_11 = new TreeNode(array('id' => 11, 'tracker_id' => 113, 'artifactlinks' => ''), 11);
        $node_9  = new TreeNode(array('id' => 9,  'tracker_id' => 93,  'artifactlinks' => ''), 9);
        $node_10 = new TreeNode(array('id' => 10, 'tracker_id' => 113, 'artifactlinks' => '66'), 10);
        $node_66 = new TreeNode(array('id' => 66, 'tracker_id' => 666, 'artifactlinks' => ''), 66);

        $root->addChildren(
            $node_7->addChildren(
                $node_5
            ),
            $node_6->addChildren(
                $node_8->addChildren(
                    $node_11,
                    $node_9
                )
            ),
            $node_10,
            $node_66
        );
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
