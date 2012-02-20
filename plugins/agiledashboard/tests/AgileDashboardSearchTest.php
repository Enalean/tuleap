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

require_once dirname(__FILE__) .'/../include/AgileDashboard/Search.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php';
Mock::generate('Project');
Mock::generate('Tracker');
Mock::generate('AgileDashboard_SearchDao');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_FormElement_Field_String');

class AgileDashboardSearchTest extends UnitTestCase {

    function testGetMatchingArtifactsWhenOneCriterion() {
        $dar = array(
            array(
                'id' => 6,
                'title' => 'As a user I want to search on shared fields',
            ),
            array(
                'id' => 8,
                'title' => 'Add the form',
            )
        );
        
        $sharedValueDar = array(
            array(
                'id' => 214,
            ),
            array(
                'id' => 143,
            )
        );
        $dao = new MockAgileDashboard_SearchDao();
        $dao->setReturnValue('searchMatchingArtifacts', $dar);
        $dao->expectOnce('searchMatchingArtifacts', array(array(array(214,143))));
        
        $dao->setReturnValue('searchSharedValueIds', $sharedValueDar);
        $dao->expectOnce('searchSharedValueIds', array(array(214)));
        
        $criteria = array('220' => array('values' => array('214')));
        
        
        $search = TestHelper::getPartialMock('AgileDashboard_Search', array('getDao'));
        $search->setReturnValue('getDao', $dao);
        
        $artifacts = $search->getMatchingArtifacts($criteria);
        $this->assertEqual($artifacts[0]['id'], 6);
        $this->assertEqual($artifacts[1]['title'], 'Add the form');
    }

    function testGetMatchingArtifactsWhenTwoDifferentCriteria() {
        $dar = array(
            array(
                'id' => 6,
                'title' => 'As a user I want to search on shared fields',
            ),
            array(
                'id' => 8,
                'title' => 'Add the form',
            )
        );
        
        $sharedValueDar214 = array(
            array(
                'id' => 214,
            ),
            array(
                'id' => 241,
            ),
        );
        $sharedValueDar250 = array(
            array(
                'id' => 250,
            ),
            array(
                'id' => 252,
            ),
        );
        
        $dao = new MockAgileDashboard_SearchDao();
        $dao->setReturnValue('searchMatchingArtifacts', $dar);
        $dao->expectOnce('searchMatchingArtifacts', array(array(array(214,241), array(250, 252))));
        
        $dao->setReturnValueAt(0, 'searchSharedValueIds', $sharedValueDar214);
        $dao->expectAt(0, 'searchSharedValueIds', array(array(214)));
        
        $dao->setReturnValueAt(1, 'searchSharedValueIds', $sharedValueDar250);
        $dao->expectAt(1, 'searchSharedValueIds', array(array(250)));
        
        $criteria = array('320' => array('values' => array('214')),
                          '330' => array('values' => array('250')));
        
        
        $search = TestHelper::getPartialMock('AgileDashboard_Search', array('getDao'));
        $search->setReturnValue('getDao', $dao);
        
        $artifacts = $search->getMatchingArtifacts($criteria);
        $this->assertEqual($artifacts[0]['id'], 6);
        $this->assertEqual($artifacts[1]['title'], 'Add the form');
    }
    
    function testGetMatchingArtifactsWithBlankCriterion() {
        $dar = array(
            array(
                'id' => 6,
                'title' => 'As a user I want to search on shared fields',
            ),
            array(
                'id' => 8,
                'title' => 'Add the form',
            )
        );
        
        $sharedValueDar214 = array(
            array(
                'id' => 214,
            ),
            array(
                'id' => 241,
            ),
        );
        $sharedValueDar250 = array(
            array(
                'id' => 250,
            ),
            array(
                'id' => 252,
            ),
        );
        
        $dao = new MockAgileDashboard_SearchDao();
        $dao->setReturnValue('searchMatchingArtifacts', $dar);
        $dao->expectOnce('searchMatchingArtifacts', array(array(array(214,241))));
        
        $dao->setReturnValueAt(0, 'searchSharedValueIds', $sharedValueDar214);
        $dao->expectAt(0, 'searchSharedValueIds', array(array(214)));
        
        $criteria = array('320' => array('values' => array('214')),
                          '330' => array('values' => array('')));
        
        
        $search = TestHelper::getPartialMock('AgileDashboard_Search', array('getDao'));
        $search->setReturnValue('getDao', $dao);
        
        $artifacts = $search->getMatchingArtifacts($criteria);
        $this->assertEqual($artifacts[0]['id'], 6);
        $this->assertEqual($artifacts[1]['title'], 'Add the form');
    }
    
    function _testGetSearchCriteriaWhenOneCriterion() {
        $dao = new MockAgileDashboard_SearchDao();
        
        $search = TestHelper::getPartialMock('AgileDashboard_Search', array('getDao'));
        $search->setReturnValue('getDao', $dao);

        $sharedValuesDar = array(
            array(
                'id' => 210,
            ),
            array(
                'id' => 220,
            )
        );
        $dao->setReturnValue('searchSharedValueIds', $sharedValuesDar);
        
        $sharedFieldsDar = array(
            array(
                'id' => 350,
            ),
            array(
                'id' => 360,
            )
        );
        $dao->setReturnValue('searchSharedFieldIds', $sharedFieldsDar);

        $criteria = array('220' => array('values' => array('214')));
        
        $searchCriteria = $search->getSearchCriteriaFromRequestCriteria($criteria);
        $this->assertEqual(count($searchCriteria), 1);
        
        $searchCriterion = $searchCriteria[0];
        
        $this->assertEquals($searchCriterion->getFieldIds(), array(350, 360));
        $this->assertEquals($searchCriterion->getValueIds(), array(210, 220));
    }
}
?>
