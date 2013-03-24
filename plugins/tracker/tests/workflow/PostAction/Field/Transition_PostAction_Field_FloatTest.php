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

Mock::generatePartial('Transition_PostAction_Field_Float', 'Transition_PostAction_Field_FloatTestVersion', array('getDao', 'addFeedback', 'getFormElementFactory', 'isDefined', 'getFieldIdOfPostActionToUpdate'));

class Transition_PostAction_Field_FloatTest extends TuleapTestCase {
    
    public function setUp() {
        parent::setUp();
        
        $this->post_action_id = 9348;
        $this->transition     = mock('Transition');
        $this->post_action    = new Transition_PostAction_Field_FloatTestVersion();
        $this->dao            = mock('Transition_PostAction_Field_FloatDao');
        $this->field          = stub('Tracker_FormElement_Field_Float')->getId()->returns(1131);
        $this->value          = 1.5;
        $this->factory        = mock('Tracker_FormElementFactory');
        $this->post_action->__construct($this->transition, $this->post_action_id, $this->field, $this->value);
        stub($this->post_action)->getDao()->returns($this->dao);
        stub($this->post_action)->isDefined()->returns($this->field);
    }
    
    public function itHandlesUpdateRequests() {
        $new_field_id = 4572;
        $new_value    = 1.5;
        $request      = aRequest()->with('workflow_postaction_field_float',       array($this->post_action_id => $new_field_id))
                                  ->with('workflow_postaction_field_float_value', array($this->post_action_id => $new_value))
                                  ->with('remove_postaction',                   array())
                                  ->build();
        
        $field = stub('Tracker_FormElement_Field_Float')->getId()->returns(4572);
        stub($this->post_action)->getFieldIdOfPostActionToUpdate()->returns($new_field_id);
        
        $this->factory->setReturnReference('getUsedFormElementById', $field, array($new_field_id));
        $this->post_action->setReturnReference('getFormElementFactory', $this->factory);
                
        stub('Tracker_FormElement_Field_Float')->validateValue()->returns(true);
        $this->dao->expectOnce('updatePostAction', array($this->post_action_id, $new_field_id, $new_value));
        $this->post_action->process($request);
    }
    
    public function itHandlesDeleteRequests() {
        $request = aRequest()->with('remove_postaction', array($this->post_action_id => 1))
                             ->build();
        
        $this->dao->expectOnce('deletePostAction', array($this->post_action_id));
        $this->post_action->process($request);
    }
    
    public function testBeforeShouldSetTheFloatField() {
        $user = mock('PFUser');
        
        stub($this->field)->getLabel()->returns('Remaining Effort');
        stub($this->field)->userCanRead($user)->returns(true);
        stub($this->field)->userCanUpdate($user)->returns(true);
        
        $expected    = 1.5;
        $fields_data = array(
            'field_id' => 'value',
        );
        
        $this->post_action->expectOnce('addFeedback', array('info', 'workflow_postaction', 'field_value_set', array($this->field->getLabel(), $expected)));
        
        $this->post_action->before($fields_data, $user);
        $this->assertEqual($expected, $fields_data[$this->field->getId()]);
    }
    
    public function testBeforeShouldBypassAndSetTheFloatField() {
        $user = mock('PFUser');
        
        $label           = stub($this->field)->getLabel()->returns('Remaining Effort');
        $readableField   = stub($this->field)->userCanRead($user)->returns(true);
        $updatableField  = stub($this->field)->userCanUpdate($user)->returns(false);
        
        $expected    = 1.5;
        $fields_data = array(
            'field_id' => 'value',
        );
        
        $this->post_action->expectOnce('addFeedback', array('info', 'workflow_postaction', 'field_value_set', array($this->field->getLabel(), $expected)));      
        $this->post_action->before($fields_data, $user);
        $this->assertEqual($expected, $fields_data[$this->field->getId()]);
    }
    
    public function testBeforeShouldNOTDisplayFeedback() {
        $user = mock('PFUser');
        
        $label           = stub($this->field)->getLabel()->returns('Remaining Effort');
        $readableField   = stub($this->field)->userCanRead($user)->returns(false);
        
        $expected    = 1.5;
        $fields_data = array(
            'field_id' => 'value',
        );
        
        $this->post_action->expectNever('addFeedback');      
        $this->post_action->before($fields_data, $user);
        $this->assertEqual($expected, $fields_data[$this->field->getId()]);
    }
    
    public function itAcceptsValue0() {
        $post_action = aFloatFieldPostAction()->withValue(0.0)->build();
        $this->assertTrue($post_action->isDefined());
    }
}
?>