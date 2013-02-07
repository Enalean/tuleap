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

Mock::generate('Tracker_CrossSearch_SharedFieldDao');

class Tracker_CrossSearch_SharedFieldFactoryTest extends UnitTestCase {
    
    function setUp() {
        parent::setUp();
        $this->factory = TestHelper::getPartialMock('Tracker_CrossSearch_SharedFieldFactory', array('getDao'));
        $this->dao     = new MockTracker_CrossSearch_SharedFieldDao();
        $this->factory->setReturnValue('getDao', $this->dao);
    }
    
    function testNoCriteriaShouldReturnNoFields() {
        $sharedFields = $this->factory->getSharedFields(null);
        $this->assertEqual($sharedFields, array());
    }
    
    function testWithOneValueForOneFieldShouldReturnTwoFieldsAndTwoValues() {
        $criteria = array('220' => array('values' => array('350')));
        
        $this->dao->setReturnValue('searchSharedFieldIds', $this->darFromIds(220, 230));
        $this->dao->setReturnValue('searchSharedValueIds', $this->darFromIds(350, 360));
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 1);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(350, 360));
    }
    
    function testWithTwoValuesForOneFieldShouldRetunTwoFieldAndFourValues() {
        $criteria = array('220' => array('values' => array('350', '351')));
        
        $this->dao->setReturnValue('searchSharedFieldIds', $this->darFromIds(220, 230));
        $this->dao->setReturnValue('searchSharedValueIds', $this->darFromIds(350, 351, 360, 361));
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 1);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(350, 351, 360, 361));
    }
    
    function testWithValuesForTwoFields() {
        $criteria = array('220' => array('values' => array('350', '351')),
                          '221' => array('values' => array('352')));
        
        $this->dao->setReturnValueAt(0, 'searchSharedFieldIds', $this->darFromIds(220, 230));
        $this->dao->setReturnValueAt(1, 'searchSharedFieldIds', $this->darFromIds(221, 231));
        $this->dao->setReturnValueAt(0, 'searchSharedValueIds', $this->darFromIds(350, 351, 360, 361), array(array('350', '351')));
        $this->dao->setReturnValueAt(1, 'searchSharedValueIds', $this->darFromIds(352, 362), array(array('352')));
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 2);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(350, 351, 360, 361));
        $this->assertEqual($sharedFields[1]->getFieldIds(), array(221, 231));
        $this->assertEqual($sharedFields[1]->getValueIds(), array(352, 362));
    }
    
    function testWithOneFieldWithNoValuesShouldReturnNoMatchingFields() {
        $criteria = array('220' => array('values' => array(0 => '')));
        
        $this->dao->expectNever('searchSharedFieldIds');
        $this->dao->expectNever('searchSharedValueIds');
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual($sharedFields, array());
    }
    
    function testWithValueNoneSelectedShouldReturnOnlyOneValueForTheTwoFields() {
        $criteria = array('220' => array('values' => array(0 => '100')));
        
        $this->dao->setReturnValue('searchSharedFieldIds', $this->darFromIds(220, 230));
        $this->dao->setReturnValue('searchSharedValueIds', array());
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 1);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(100));
    }
    
    function testWithValueNoneSelectedAndAnotherOneShouldReturnNoneValueAndTheTwoOtherValues() {
        $criteria = array('220' => array('values' => array('100', '350')));
        
        $this->dao->setReturnValue('searchSharedFieldIds', $this->darFromIds(220, 230));
        $this->dao->setReturnValue('searchSharedValueIds', $this->darFromIds(350, 360));
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 1);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(100, 350, 360));
    }
    
    /**
     * Returns a Dar object that would contains rows with 'id' parameter.
     * 
     * Example:
     * darFromIds(220, 230) -> [['id' => 220],['id' => 230]]
     * 
     */
    private function darFromIds() {
        $arrayToDarParams = array();
        $argList  = func_get_args();
        foreach ($argList as $id) {
            $arrayToDarParams[] = array('id' => $id);
        }
        return call_user_func_array(array('TestHelper', 'arrayToDar'), $arrayToDarParams);
    }
}

?>
