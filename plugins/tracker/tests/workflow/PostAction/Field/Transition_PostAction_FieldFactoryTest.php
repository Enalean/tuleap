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

    public function setUp() {
        parent::setUp();

        $this->transition_id  = 123;
        $this->field_id       = 456;
        $this->post_action_id = 789;

        $this->transition = aTransition()->withId($this->transition_id)->build();
    }

    public function itLoadsIntFieldPostActions() {
        $post_action_value = 12;
        $post_action_rows  = array(array('id'       => $this->post_action_id,
                                         'field_id' => $this->field_id,
                                         'value'    => $post_action_value));

        $int_dao             = stub('Transition_PostAction_Field_IntDao')->searchByTransitionId($this->transition_id)->returns($post_action_rows);
        $field               = mock('Tracker_FormElement_Field_Integer');
        $formelement_factory = stub('Tracker_FormElementFactory')->getFormElementById($this->field_id)->returns($field);

        $factory = aPostActionFieldFactory()->withFieldIntDao($int_dao)
                                       ->withFormElementFactory($formelement_factory)
                                       ->build();

        $this->assertEqual($factory->loadPostActions($this->transition),
                           array(anIntFieldPostAction()->withId($this->post_action_id)
                                                       ->withField($field)
                                                       ->withTransition($this->transition)
                                                       ->withValue($post_action_value)
                                                       ->build()));
    }

    public function itLoadsFloatFieldPostActions() {
        $post_action_value = 3.45;
        $post_action_rows  = array(array('id'       => $this->post_action_id,
                                         'field_id' => $this->field_id,
                                         'value'    => $post_action_value));

        $float_dao            = stub('Transition_PostAction_Field_FloatDao')->searchByTransitionId($this->transition_id)->returns($post_action_rows);
        $field                = mock('Tracker_FormElement_Field_Float');
        $form_element_factory = stub('Tracker_FormElementFactory')->getFormElementById($this->field_id)->returns($field);

        $factory = aPostActionFieldFactory()->withFieldFloatDao($float_dao)
                                       ->withFormElementFactory($form_element_factory)
                                       ->build();

        $this->assertEqual($factory->loadPostActions($this->transition),
                           array(aFloatFieldPostAction()->withId($this->post_action_id)
                                                        ->withField($field)
                                                        ->withTransition($this->transition)
                                                        ->withValue($post_action_value)
                                                        ->build()));
    }
}
?>
