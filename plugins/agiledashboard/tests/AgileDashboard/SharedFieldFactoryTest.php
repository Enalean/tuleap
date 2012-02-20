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

class AgileDashboard_SharedFieldDao {
    public function searchSharedFieldIds($fieldId) {}
    public function searchSharedValueIds($valueId) {}
}

class AgileDashboard_SharedFieldFactory {
    public function getSharedFields($criteria) {
        $sharedFields = array();
        
        if ($criteria) {
            foreach($criteria as $fieldId => $data) {
                $valueId = $data['values'][0];
            
                $fieldIds = $this->getDao()->searchSharedFieldIds($fieldId);
                $valueIds = $this->getDao()->searchSharedValueIds($valueId);

                $sharedField = new AgileDashboard_SharedField($fieldIds, $valueIds);
                $sharedFields[] = $sharedField;
            }
        }
        
        return $sharedFields;
    }
}

Mock::generate('AgileDashboard_SharedFieldDao');

class AgileDashboard_SharedFieldFactoryTest extends UnitTestCase {
    
    function setUp() {
        $this->factory = TestHelper::getPartialMock('AgileDashboard_SharedFieldFactory', array('getDao'));
        $this->dao     = new MockAgileDashboard_SharedFieldDao();
        $this->factory->setReturnValue('getDao', $this->dao);
    }
    
    function testWithNoCriteria() {
        $sharedFields = $this->factory->getSharedFields(null);
        $this->assertEqual($sharedFields, array());
    }
    
    function testWithOneValueForOneField() {
        $criteria = array('220' => array('values' => array('350')));
        
        $this->dao->setReturnValue('searchSharedFieldIds', array(220, 230));
        $this->dao->setReturnValue('searchSharedValueIds', array(350, 360));
        
        $sharedFields = $this->factory->getSharedFields($criteria);
        $this->assertEqual(count($sharedFields), 1);
        $this->assertEqual($sharedFields[0]->getFieldIds(), array(220, 230));
        $this->assertEqual($sharedFields[0]->getValueIds(), array(350, 360));
    }
    
    function testWithTwoValuesForOneField() {}
    function testWithValuesForTwoFields() {}
}

?>
