<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once __DIR__.'/../bootstrap.php';
Mock::generate('Transition_PostActionFactory');

Mock::generate('Tracker_FormElement_Field_Date');

Mock::generate('Tracker_FormElement_Field_List_Value');



class TransitionFactory_BaseTest extends TuleapTestCase {

    /** @var TransitionFactory */
    protected $factory;

    /** @var Workflow_Transition_ConditionFactory */
    protected $condition_factory;

    public function setUp() {
        parent::setUp();
        $this->condition_factory  = mock('Workflow_Transition_ConditionFactory');
        $this->postaction_factory = mock('Transition_PostActionFactory');
        $this->factory            = partial_mock('TransitionFactory',
                array('getPostActionFactory'),
                array($this->condition_factory));
        stub($this->factory)->getPostActionFactory()->returns($this->postaction_factory);

        $this->project = mock('Project');
    }
}

class TransitionFactory_isFieldUsedInTransitionsTest extends TransitionFactory_BaseTest {

    private $a_field_not_used_in_transitions;
    private $a_field_used_in_post_actions;
    private $a_field_used_in_conditions;

    public function setUp() {
        parent::setUp();
        $this->a_field_not_used_in_transitions = mock('Tracker_FormElement_Field_Date');
        stub($this->a_field_not_used_in_transitions)->getId()->returns(1002);

        $this->a_field_used_in_post_actions = mock('Tracker_FormElement_Field_Date');
        stub($this->a_field_used_in_post_actions)->getId()->returns(1003);

        $this->a_field_used_in_conditions = mock('Tracker_FormElement_Field_Date');
        stub($this->a_field_used_in_conditions)->getId()->returns(1004);

        stub($this->postaction_factory)->isFieldUsedInPostActions($this->a_field_not_used_in_transitions)->returns(false);
        stub($this->postaction_factory)->isFieldUsedInPostActions($this->a_field_used_in_post_actions)->returns(true);
        stub($this->postaction_factory)->isFieldUsedInPostActions($this->a_field_used_in_conditions)->returns(false);

        stub($this->condition_factory)->isFieldUsedInConditions($this->a_field_not_used_in_transitions)->returns(false);
        stub($this->condition_factory)->isFieldUsedInConditions($this->a_field_used_in_post_actions)->returns(false);
        stub($this->condition_factory)->isFieldUsedInConditions($this->a_field_used_in_conditions)->returns(true);
    }

    public function itReturnsTrueIfFieldIsUsedInPostActions() {
        $this->assertTrue($this->factory->isFieldUsedInTransitions($this->a_field_used_in_post_actions));
    }

    public function itReturnsTrueIfFieldIsUsedInConditions() {
        $this->assertTrue($this->factory->isFieldUsedInTransitions($this->a_field_used_in_conditions));
    }

    public function itReturnsFalseIsNiotUsedInTransitions() {
        $this->assertFalse($this->factory->isFieldUsedInTransitions($this->a_field_not_used_in_transitions));
    }
}

class TransitionFactory_duplicateTest extends TransitionFactory_BaseTest {

    public function testDuplicate() {
        $field_value_new = new MockTracker_FormElement_Field_List_Value();
        $field_value_new->setReturnValue('getId', 2066);
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2067);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2068);

        $t1  = new Transition(1, 1, $field_value_new, $field_value_analyzed);
        $t2  = new Transition(2, 1, $field_value_analyzed, $field_value_accepted);
        $t3  = new Transition(3, 1, $field_value_analyzed, $field_value_new);
        $transitions = array($t1, $t2, $t3);

        $tf = partial_mock(
            'TransitionFactory',
            array('addTransition', 'getPostActionFactory', 'duplicatePermissions'),
            array($this->condition_factory)
        );

        $values = array(
            2066  => 3066,
            2067  => 3067,
            2068  => 3068
        );

        $tf->expectCallCount('addTransition', 3, 'Method addTransition should be called 3 times.');
        $tf->expectAt(0, 'addTransition', array(1, 3066, 3067));
        $tf->expectAt(1, 'addTransition', array(1, 3067, 3068));
        $tf->expectAt(2, 'addTransition', array(1, 3067, 3066));
        $tf->setReturnValueAt(0, 'addTransition', 101);
        $tf->setReturnValueAt(1, 'addTransition', 102);
        $tf->setReturnValueAt(2, 'addTransition', 103);

        expect($this->condition_factory)->duplicate()->count(3);
        expect($this->condition_factory)->duplicate($t1, 101, array(), false, false)->at(0);
        expect($this->condition_factory)->duplicate($t2, 102, array(), false, false)->at(1);
        expect($this->condition_factory)->duplicate($t3, 103, array(), false, false)->at(2);

        $tpaf = new MockTransition_PostActionFactory();
        $tpaf->expectCallCount('duplicate', 3, 'Method duplicate should be called 3 times.');
        $tpaf->expectAt(0, 'duplicate', array($t1, 101, array()));
        $tpaf->expectAt(1, 'duplicate', array($t2, 102, array()));
        $tpaf->expectAt(2, 'duplicate', array($t3, 103, array()));
        $tf->setReturnValue('getPostActionFactory', $tpaf);

        $tf->duplicate($values, 1, $transitions, array(), false, false);
    }
}

class TransitionFactory_GetInstanceFromXmlTest extends TransitionFactory_BaseTest {

    public function setUp() {
        parent::setUp();
        $this->field = aMockField()->build();
        $this->from_value  = mock('Tracker_FormElement_Field_List_Value');
        $this->to_value    = mock('Tracker_FormElement_Field_List_Value');
        $this->xml_mapping = array('F1'     => $this->field,
                                   'F32-V1' => $this->from_value,
                                   'F32-V0' => $this->to_value);

        stub($this->condition_factory)->getAllInstancesFromXML()->returns(new Workflow_Transition_ConditionsCollection());
    }

    public function itReconstitutesPostActions() {

        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <postactions>
                    <postaction_field_date valuetype="1">
                        <field_id REF="F1"/>
                    </postaction_field_date>
                </postactions>
            </transition>
        ');

        expect($this->postaction_factory)->getInstanceFromXML($xml->postactions, $this->xml_mapping, '*')->once();
        $transition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);
    }

    public function itReconsititutesPermissions() {
        $xml = new SimpleXMLElement('
            <transition>
                <from_id REF="F32-V1"/>
                <to_id REF="F32-V0"/>
                <permissions>
                    <permission ugroup="UGROUP_PROJECT_MEMBERS"/>
                    <permission ugroup="UGROUP_PROJECT_ADMIN"/>
                </permissions>
            </transition>
        ');

        expect($this->condition_factory)->getAllInstancesFromXML()->once();
        $transition = $this->factory->getInstanceFromXML($xml, $this->xml_mapping, $this->project);

        $this->assertIsA($transition->getConditions(), 'Workflow_Transition_ConditionsCollection');
    }
}
