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

class Tracker_CrossSearch_CriteriaBuilderTest extends TuleapTestCase {
    public $planning_trackers;
    public $formElementFactory;
    public $semantic_factory;
    
    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->semantic_factory   = new MockTracker_CrossSearch_SemanticValueFactory();
        $this->planning_trackers  = array();
    }
    
    public function getPartiallyMockedCriteriaBuilder($returnValue) {
        $builder  = TestHelper::getPartialMock('Tracker_CrossSearch_CriteriaBuilder', array('getArtifactByTracker'));
        $builder->__construct($this->formElementFactory, $this->semantic_factory, $this->planning_trackers);
        $builder->setReturnValue('getArtifactByTracker', $returnValue);
        return $builder;
    }
}

class Tracker_CrossSearch_CriteriaBuilder_WithSharedFieldCriteriaTest extends Tracker_CrossSearch_CriteriaBuilderTest {


    public function testNoValueSubmittedShouldNotSelectAnythingInCriterion() {
        $this->shared_field_criteria = array();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getSharedFieldsCriteria();
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array());
    }
    
    public function testSubmittedValueIsSelectedInCriterion() {
        $this->shared_field_criteria = array('220' => array('values' => array('350')));
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getSharedFieldsCriteria();
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350));
    }
    
    public function testSubmittedValuesAreSelectedInCriterion() {
        $this->shared_field_criteria = array('220' => array('values' => array('350', '351')),
                                '221' => array('values' => array('352')));
        
        $fields = array(aTextField()->withId(220)->build(),
                        aTextField()->withId(221)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getSharedFieldsCriteria();
        $this->assertEqual(count($criteria), 2);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350, 351));
        $this->assertEqual($criteria[1]->field->getCriteriaValue($criteria[1]), array(352));
    }    
    
    private function getSharedFieldsCriteria($returnValue = array()) {
        $criteria_builder      = new Tracker_CrossSearch_CriteriaBuilder($this->formElementFactory, $this->semantic_factory, array());
        $cross_search_criteria = aCrossSearchCriteria()->withSharedFieldsCriteria($this->shared_field_criteria)->build();
    
        $project               = new MockProject();
        $report                = new MockTracker_Report();
        return $criteria_builder->getSharedFieldsCriteria($project, $report, $cross_search_criteria);
    }

}

class Tracker_CrossSearch_CriteriaBuilder_WithAllCriteriaTypesTest extends Tracker_CrossSearch_CriteriaBuilderTest {

    public function testAllCriteriaHaveAReport() {
        $criteria = $this->givenACriteriaWith_SharedField_SemanticTitle_SemanticStatus_Artifact();
        foreach ($criteria as $criterion) {
            $this->assertIsA($criterion->report, 'Tracker_Report');
        }
    }
    
    public function testAllCriteriaAreAdvancedCriteria() {
        $criteria = $this->givenACriteriaWith_SharedField_SemanticTitle_SemanticStatus_Artifact();
        foreach ($criteria as $criterion) {
            $this->assertTrue($criterion->is_advanced);
        }
    }
    
    public function testGetCriteriaAssemblesAllCriterias() {
        $criteria = $this->givenACriteriaWith_SharedField_SemanticTitle_SemanticStatus_Artifact();
                
        $this->assertEqual(count($criteria), 4);        
    }
    
    private function givenACriteriaWith_SharedField_SemanticTitle_SemanticStatus_Artifact() {
        $release_tracker_id = 133;
        $release_tracker    = aTracker()->withId($release_tracker_id)->build();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $this->shared_field_criteria = array('220' => array('values' => array('350', '351')));
        $this->semantic_criteria     = array('title' => 'Foo', 'status' => '');
        $this->artifact_criteria     = array($release_tracker_id => array(3, 6));
        $this->planning_trackers     = array($release_tracker);
        
        $returnValue   = array(New Tracker_Artifact(3, 133, null, null, null));
        $returnValue[] = New Tracker_Artifact(6, 133, null, null, null);
        
        return $this->getCriteria($returnValue);
    }

    
    private function getCriteria($returnValue) {
        $criteria_builder      = $this->getPartiallyMockedCriteriaBuilder($returnValue);
        $cross_search_criteria = aCrossSearchCriteria()
                                ->withSharedFieldsCriteria($this->shared_field_criteria)
                                ->withSemanticCriteria($this->semantic_criteria)
                                ->withArtifactIds($this->artifact_criteria)
                                ->build();
        $project               = new MockProject();
        $report                = new MockTracker_Report();
        return $criteria_builder->getCriteria($project, $report, $cross_search_criteria);
    }
    
}

class Tracker_CrossSearch_CriteriaBuilder_WithSemanticTest extends Tracker_CrossSearch_CriteriaBuilderTest {
    
    public function itPassesTheSearchedTitleToTheField() {
        
        $cross_search_criteria = aCrossSearchCriteria()
                                ->withSemanticCriteria(array('title' => 'Foo', 'status' => ''))
                                ->build();
        $report_criteria       = $this->getSemanticCriteria($cross_search_criteria);
        
        $actual_field          = $report_criteria[0]->field;
        $expected_field        = new Tracker_CrossSearch_SemanticTitleReportField('Foo', $this->semantic_factory);
        
        $this->assertEqual($expected_field, $actual_field);
    }

    public function itPassesTheSearchedStatusToTheField() {
        $cross_search_criteria = aCrossSearchCriteria()
                                ->forOpenItems()
                                ->build();
        $report_criteria       = $this->getSemanticCriteria($cross_search_criteria);
        $actual_field          = $report_criteria[1]->field;
        $expected_field        = new Tracker_CrossSearch_SemanticStatusReportField(Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN,
                                                                                   new MockTracker_CrossSearch_SemanticValueFactory());
        
        $this->assertEqual($expected_field, $actual_field);
    }
    
    protected function getSemanticCriteria($cross_search_criteria) {
        $builder = new Tracker_CrossSearch_CriteriaBuilder($this->formElementFactory, $this->semantic_factory, array());
        $report  = new MockTracker_Report();
        return $builder->getSemanticFieldsCriteria($report, $cross_search_criteria);
    }
}

class Tracker_CrossSearch_CriteriaBuilder_WithNoArtifactIDTest extends Tracker_CrossSearch_CriteriaBuilderTest {
    
    public function itDoesntCreateACriteriaAtAllWhenArtifactIdsArentSet() {
        $criteria = aCrossSearchCriteria()->build();
        $report   = new MockTracker_Report();
        
        $builder  = $this->getPartiallyMockedCriteriaBuilder(array());
        $artifact_criteria = $builder->getArtifactLinkCriteria($report, $criteria);
        
        $this->assertEqual(array(), $artifact_criteria);
    }       
    
    public function itDoesntCreateACriteriaAtAllWhenArtifactIdsAreEmpty() {
        $criteria = aCrossSearchCriteria()->withArtifactIds(array())->build();
        $report   = new MockTracker_Report();
        $builder  = $this->getPartiallyMockedCriteriaBuilder(array());
        $artifact_criteria = $builder->getArtifactLinkCriteria($report, $criteria);
        
        $this->assertEqual(array(), $artifact_criteria);
    }       
}

class Tracker_CrossSearch_CriteriaBuilder_WithOneArtifactListTest extends Tracker_CrossSearch_CriteriaBuilderTest {
    
    public function itCreatesASingleArtifactIdCriterion() {
        $release_tracker_id      = 999;
        $release_tracker         = aTracker()->withId($release_tracker_id)->build();
        $criteria                = aCrossSearchCriteria()->withArtifactIds(array($release_tracker_id => array(1)))->build();
        $report                  = new MockTracker_Report();
        
        $artifact                = new Tracker_Artifact(1, $release_tracker_id, null, null, null);
        $this->planning_trackers = array($release_tracker);
        $builder                 = $this->getPartiallyMockedCriteriaBuilder(array($artifact));
        $artifact_criteria       = $builder->getArtifactLinkCriteria($report, $criteria);

        $expected_criterion      = new Tracker_CrossSearch_ArtifactLinkReportField($release_tracker, array($artifact));
        $this->assertEqual(count($artifact_criteria), 1);
        $this->assertNotNull($artifact_criteria[0]);

        $this->assertEqual($artifact_criteria[0]->field, $expected_criterion);
    }    
}

class Tracker_CrossSearch_CriteriaBuilder_WithSeveralArtifactListsTest extends Tracker_CrossSearch_CriteriaBuilderTest {
    
    public function itCreatesSeveralArtifactIdCriteria() {
        $release_tracker_id      = 999;
        $release_tracker         = aTracker()->withId($release_tracker_id)->build();
        
        $sprint_tracker_id       = 666;
        $sprint_tracker          = aTracker()->withId($sprint_tracker_id)->build();
        
        $artifacts_ids           = array($release_tracker_id => array(1, 512), $sprint_tracker_id => array(33));
        $criteria                = aCrossSearchCriteria()->withArtifactIds($artifacts_ids)->build();
        $report                  = new MockTracker_Report();
        
        $artifact1               = new Tracker_Artifact(1, $release_tracker_id, null, null, null);
        $artifact512             = new Tracker_Artifact(512, $release_tracker_id, null, null, null);
        $artifact33              = new Tracker_Artifact(33, $sprint_tracker_id, null, null, null);
        
        $this->planning_trackers = array($release_tracker, $sprint_tracker);
        
        $builder  = TestHelper::getPartialMock('Tracker_CrossSearch_CriteriaBuilder', array('getArtifactByTracker'));
        $builder->__construct($this->formElementFactory, $this->semantic_factory, $this->planning_trackers);
        $builder->setReturnValue('getArtifactByTracker', array($artifact1, $artifact512), array($release_tracker_id));
        $builder->setReturnValue('getArtifactByTracker', array($artifact33), array($sprint_tracker_id));
                        
        $artifact_criteria       = $builder->getArtifactLinkCriteria($report, $criteria);

        $expected_criterion1     = new Tracker_CrossSearch_ArtifactLinkReportField($release_tracker, array($artifact1, $artifact512));
        $expected_criterion2     = new Tracker_CrossSearch_ArtifactLinkReportField($sprint_tracker, array($artifact33));
        
        $this->assertEqual(count($artifact_criteria), 2);
        $this->assertEqual($artifact_criteria[0]->field, $expected_criterion1);
        $this->assertEqual($artifact_criteria[1]->field, $expected_criterion2);
        
    }
    
    
}

?>
