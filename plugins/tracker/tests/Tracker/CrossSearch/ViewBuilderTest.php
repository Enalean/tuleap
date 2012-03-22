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
require_once dirname(__FILE__) . '/../../../include/Tracker/TrackerFactory.class.php';
require_once 'common/include/Codendi_Request.class.php';

Mock::generate('Tracker_FormElementFactory');
Mock::generate('Project');
Mock::generate('Tracker_Report');

class Fake_Tracker_CrossSearch_SearchContentView extends Tracker_CrossSearch_SearchContentView {
}

class Tracker_CrossSearch_ViewBuilderTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->formElementFactory = new MockTracker_FormElementFactory();
    }

    public function itBuildCustomContentView() {
        $formElementFactory = new MockTracker_FormElementFactory();
        $formElementFactory->setReturnValue('getProjectSharedFields', array());
        $tracker_factory    = new MockTrackerFactory();
        $tracker_factory->setReturnValue('getTrackersByGroupId', array());
        $project            = new MockProject();
        $request_criteria   = array();
        $search             = new MockTracker_CrossSearch_Search();
        $search->setReturnValue('getHierarchicallySortedArtifacts', new TreeNode());

        $builder   = new Tracker_CrossSearch_ViewBuilder($formElementFactory, $tracker_factory, $search);
        $classname = 'Fake_Tracker_CrossSearch_SearchContentView';
        $view      = $builder->buildCustomContentView($classname, $project, $request_criteria, array());
        $this->assertIsA($view, $classname);
    }
    

    public function testNoValueSubmittedShouldNotSelectAnythingInCriterion() {
        $this->request = new Codendi_Request(array(
            'group_id' => '66',
            'criteria' => array()
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
            'criteria' => array('220' => array('values' => array('350')))
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
                                '221' => array('values' => array('352')))
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
        $searchViewBuilder = new Tracker_CrossSearch_ViewBuilder($this->formElementFactory, new MockTrackerFactory(), new MockTracker_CrossSearch_Search());
        return $searchViewBuilder->getCriteria($project, $report, $this->request->get('criteria'));
    }

}
class Tracker_CrossSearch_ViewBuilder_BuildViewTest extends TuleapTestCase {
    public function itThrowsAnExceptionIfTheServiceTrackerIsntActivated() {
        $this->expectException('Tracker_CrossSearch_ServiceNotUsedException');
        $project            = new MockProject();
        $builder   = new Tracker_CrossSearch_ViewBuilder(new MockTracker_FormElementFactory(), new MockTrackerFactory(), new MockTracker_CrossSearch_Search());
        $builder->buildView($project, array());
    }
    
    
}
?>
