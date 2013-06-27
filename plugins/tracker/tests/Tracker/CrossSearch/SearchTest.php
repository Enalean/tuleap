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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

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
        stub($this->searchDao)->searchMatchingArtifacts()->returns(array());
        
        $this->search->getMatchingArtifacts($this->user, $this->project, $this->trackerIds, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoCriteria() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = aCrossSearchCriteria()
                ->withSharedFieldsCriteria(array('220' => array('values' => array(''))))
                ->build();
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, $criteria, $this->trackerIds, null, array('title' => '', 'status' => 'any'), $this->artifact_link_field_ids, array()));

        $this->search->getMatchingArtifacts($this->user, $this->project, $this->trackerIds, $tracker_hierarchy, $criteria);
    }
    
    function testGetProjectArtifactsWhenNoArtifactsAndNoTrackers() {
        $tracker_hierarchy = $this->GivenATrackerHierarchy();
        $criteria  = aCrossSearchCriteria()
                ->withSharedFieldsCriteria(array('220' => array('values' => array(''))))
                ->build();
        
        $this->searchDao->expectOnce('searchMatchingArtifacts', array($this->user, $this->project_id, $criteria, array(), null, array('title' => '', 'status' => 'any'), $this->artifact_link_field_ids, array()));
        
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
        stub($this->searchDao)->searchMatchingArtifacts()->returns(array());
        
        $this->search = new Tracker_CrossSearch_Search($this->sharedFieldFactory, $this->searchDao, $this->hierarchy_factory, $this->artifact_link_field_ids);
        $this->search->getMatchingArtifacts($this->user, $this->project, array(), $tracker_hierarchy, $query);
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
