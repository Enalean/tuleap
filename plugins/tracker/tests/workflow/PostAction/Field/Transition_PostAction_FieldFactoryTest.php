<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once dirname(__FILE__).'/../../../builders/aPostActionFieldFactory.php';
require_once dirname(__FILE__).'/../../../builders/anIntFieldPostAction.php';
require_once dirname(__FILE__).'/../../../builders/aFloatFieldPostAction.php';

class Transition_PostAction_FieldFactoryTest extends TuleapTestCase {

    protected $factory;
    
    public function setUp() {
        parent::setUp();

        $this->transition_id  = 123;
        $this->field_id       = 456;
        $this->post_action_id = 789;

        $this->transition = aTransition()->withId($this->transition_id)->build();
    }

    public function itLoadsIntFieldPostActions() {
        $factory = aPostActionFieldFactory(array('loadPostActionRows'))->build();
        
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value'    => $post_action_value
                )
            );

        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns($post_action_rows);
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns(array());
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns(array());
        
        
        $post_action_array = $factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);
  
        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $post_action_value);
    }

    public function itLoadsFloatFieldPostActions() {
        $factory = aPostActionFieldFactory(array('loadPostActionRows'))->build();
        
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value'    => $post_action_value
                )
            );

        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns(array());
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns($post_action_rows);
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns(array());
        
        
        $post_action_array = $factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);
  
        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValue(), $post_action_value);
    }
    
    public function itLoadsDateFieldPostActions() {
        $factory = aPostActionFieldFactory(array('loadPostActionRows'))->build();
        
        $post_action_value = 12;
        $post_action_rows  = array(
            array(
                'id'       => $this->post_action_id,
                'field_id' => $this->field_id,
                'value_type'    => $post_action_value
                )
            );

        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Int::SHORT_NAME)
            ->returns(array());
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Float::SHORT_NAME)
            ->returns(array());
        stub($factory)
            ->loadPostActionRows($this->transition, Transition_PostAction_Field_Date::SHORT_NAME)
            ->returns($post_action_rows);
        
        
        $post_action_array = $factory->loadPostActions($this->transition);
        $this->assertCount($post_action_array, 1);
  
        $first_pa = $post_action_array[0];
        $this->assertEqual($first_pa->getId(), $this->post_action_id);
        $this->assertEqual($first_pa->getTransition(), $this->transition);
        $this->assertEqual($first_pa->getValueType(), $post_action_value);
    }
}
?>
