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

require_once dirname(__FILE__) .'/../../../../../include/constants.php';
require_once TRACKER_BASE_DIR .'/workflow/Transition/Condition/FieldNotEmpty/Factory.class.php';

class Workflow_Transition_Condition_FieldNotEmpty_FactoryTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        Tracker_FormElementFactory::setInstance(mock('Tracker_FormElementFactory'));
        $this->dao         = mock('Workflow_Transition_Condition_FieldNotEmpty_Dao');
        $this->factory     = new Workflow_Transition_Condition_FieldNotEmpty_Factory($this->dao);
        $this->transition  = stub('Transition')->getId()->returns(42);
        $field_string      = stub('Tracker_FormElement_Field_String')->getId()->returns(123);
        $this->xml_mapping = array('F14' => $field_string);
    }
    
    public function tearDown() {
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }

    public function itReconstitutesANotEmptyCondition() {
        $xml = new SimpleXMLElement('
            <condition type="notempty">
                <field REF="F14"/>
            </condition>
        ');

        $expected  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $expected->setFieldId(123);

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertEqual($condition, $expected);
    }

    public function itDoesNotReconstitutesAnythingIfThereIsNoRefToField() {
        $xml = new SimpleXMLElement('
            <condition type="notempty" />
        ');

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertNull($condition);
    }
}
?>
