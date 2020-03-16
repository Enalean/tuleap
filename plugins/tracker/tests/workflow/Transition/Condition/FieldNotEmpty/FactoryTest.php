<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
require_once __DIR__ . '/../../../../bootstrap.php';

class Workflow_Transition_Condition_FieldNotEmpty_FactoryTest extends TuleapTestCase
{

    private $field_id = 3;

    public function setUp()
    {
        parent::setUp();
        $this->field            = stub('Tracker_FormElement_Field_String')->getId()->returns($this->field_id);
        $element_factory        = mock('Tracker_FormElementFactory');
        stub($element_factory)->getFormElementById($this->field_id)->returns($this->field);
        Tracker_FormElementFactory::setInstance($element_factory);
        $this->dao              = mock('Workflow_Transition_Condition_FieldNotEmpty_Dao');
        $this->factory          = new Workflow_Transition_Condition_FieldNotEmpty_Factory($this->dao, $element_factory);
        $this->transition       = stub('Transition')->getId()->returns(42);
        $this->field_string     = stub('Tracker_FormElement_Field_String')->getId()->returns(0);
        $this->field_string_f15 = stub('Tracker_FormElement_Field_String')->getId()->returns(1);
        $this->xml_mapping  = array(
            'F14' => $this->field_string,
            'F15' => $this->field_string_f15
        );
    }

    public function tearDown()
    {
        Tracker_FormElementFactory::clearInstance();
        parent::tearDown();
    }

    public function itReconstitutesANotEmptyCondition()
    {
        $xml = new SimpleXMLElement('
            <condition type="notempty">
                <field REF="F14"/>
                <field REF="F15"/>
            </condition>
        ');

        $expected  = new Workflow_Transition_Condition_FieldNotEmpty($this->transition, $this->dao);
        $expected->addField($this->field_string);
        $expected->addField($this->field_string_f15);

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertEqual($condition, $expected);
    }

    public function itDoesNotReconstitutesAnythingIfThereIsNoRefToField()
    {
        $xml = new SimpleXMLElement('
            <condition type="notempty" />
        ');

        $condition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->transition);
        $this->assertNull($condition);
    }

    public function itDuplicateConditionInDatabase()
    {
        $new_transition_id = 2;
        $field_mapping     = array('some fields mapping');
        $ugroup_mapping    = array('some ugroups mapping');
        $duplicate_type    = PermissionsDao::DUPLICATE_NEW_PROJECT;

        expect($this->dao)->duplicate($this->transition->getId(), $new_transition_id, $field_mapping)->once();
        $this->factory->duplicate($this->transition, $new_transition_id, $field_mapping, $ugroup_mapping, $duplicate_type);
    }

    public function itChecksThatFieldIsNotUsed()
    {
        stub($this->dao)->isFieldUsed($this->field_id)->once()->returns(true);
        $this->assertTrue($this->factory->isFieldUsedInConditions($this->field));
    }
}
