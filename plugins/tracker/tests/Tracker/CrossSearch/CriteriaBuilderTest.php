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

class Tracker_CrossSearch_CriteriaBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
        $this->semantic_factory = new MockTracker_CrossSearch_SemanticValueFactory();
    }

    public function testNoValueSubmittedShouldNotSelectAnythingInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66',
            'criteria' => array(),
            'semantic_criteria' => array('title' => '', 'status' => '')
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getCriteria($project, $report);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array());
    }
    
    public function testSubmittedValueIsSelectedInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => array('220' => array('values' => array('350'))),
            'semantic_criteria' => array('title' => '', 'status' => '')
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getCriteria($project, $report);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350));
    }
    
    public function testSubmittedValuesAreSelectedInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66', 
            'criteria' => array('220' => array('values' => array('350', '351')),
                                '221' => array('values' => array('352'))),
            'semantic_criteria' => array('title' => '', 'status' => '')
        ));
        
        $project = new MockProject();
        $report  = new MockTracker_Report();
        
        $fields = array(aTextField()->withId(220)->build(),
                        aTextField()->withId(221)->build());
        $this->formElementFactory->setReturnValue('getProjectSharedFields', $fields);
        
        $criteria = $this->getCriteria($project, $report);
        $this->assertEqual(count($criteria), 2);
        $this->assertEqual($criteria[0]->field->getCriteriaValue($criteria[0]), array(350, 351));
        $this->assertEqual($criteria[1]->field->getCriteriaValue($criteria[1]), array(352));
    }
    
    private function getCriteria($project, $report) {
        $searchViewBuilder     = new Tracker_CrossSearch_CriteriaBuilder($this->formElementFactory, $this->semantic_factory);
        $cross_search_criteria = aCrossSearchCriteria()
                                ->withSharedFieldsCriteria($this->request->get('criteria'))
                                ->build();
        
        return $searchViewBuilder->getSharedFieldsCriteria($project, $report, $cross_search_criteria);
    }

}


class Tracker_CrossSearch_ViewBuilder_WithTitleTest extends TuleapTestCase {
    public function itPassesTheSearchedTitleToTheField() {
        $semantic_factory      = new MockTracker_CrossSearch_SemanticValueFactory();
        $builder               = new Tracker_CrossSearch_CriteriaBuilder(new MockTracker_FormElementFactory(), $semantic_factory);
        $report                = new MockTracker_Report();
        $cross_search_criteria = aCrossSearchCriteria()
                                ->withSemanticCriteria(array('title' => 'Foo', 'status' => ''))
                                ->build();
        $report_criteria       = $builder->getSemanticFieldsCriteria($report, $cross_search_criteria);
        $actual_field          = $report_criteria[0]->field;
        $expected_field        = new Tracker_CrossSearch_SemanticTitleReportField('Foo');
        
        $this->assertEqual($expected_field, $actual_field);
    }
}

class Tracker_CrossSearch_CriteriaBuilder_SemanticStatusFieldTest extends TuleapTestCase {

    public function itPassesTheSearchedStatusToTheField() {
        $semantic_factory      = new MockTracker_CrossSearch_SemanticValueFactory();
        $builder               = new Tracker_CrossSearch_CriteriaBuilder(new MockTracker_FormElementFactory(), $semantic_factory);
        $report                = new MockTracker_Report();
        $cross_search_criteria = aCrossSearchCriteria()
                                ->forOpenItems()
                                ->build();
        $report_criteria       = $builder->getSemanticFieldsCriteria($report, $cross_search_criteria);
        $actual_field          = $report_criteria[1]->field;
        $expected_field        = new Tracker_CrossSearch_SemanticStatusReportField(Tracker_CrossSearch_SemanticStatusReportField::STATUS_OPEN, $semantic_factory);
        
        $this->assertEqual($expected_field, $actual_field);
    }
}

?>
