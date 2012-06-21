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

require_once dirname(__FILE__).'/../../../../include/workflow/PostAction/Field/Transition_PostAction_Field_Int.class.php';
require_once dirname(__FILE__).'/../../../../../../tests/simpletest/common/include/builders/aRequest.php';
require_once  dirname(__FILE__).'/../../../../include/workflow/PostAction/Field/dao/Transition_PostAction_Field_IntDao.class.php';

Mock::generatePartial('Transition_PostAction_Field_Int', 'Transition_PostAction_Field_IntTestVersion', array('getDao'));

class Transition_PostAction_Field_IntTest extends TuleapTestCase {
    
    public function itHandlesUpdateRequests() {
        $post_action_id = 9348;
        $transition     = mock('Transition');
        $post_action    = new Transition_PostAction_Field_IntTestVersion();
        $dao            = mock('Transition_PostAction_Field_IntDao');
        
        $old_field      = stub('Tracker_FormElement_Field_Integer')->getId()->returns(1131);
        $old_value      = 0;
        
        $new_field_id   = 4572;
        $new_value      = 10;
        
        $post_action->__construct($transition, $post_action_id, $old_field, $old_value);
        stub($post_action)->getDao()->returns($dao);
        
        $request = aRequest()->with('workflow_postaction_field_int',       array($post_action_id => $new_field_id))
                             ->with('workflow_postaction_field_int_value', array($post_action_id => $new_value))
                             ->with('remove_postaction',                   array())
                             ->build();
        
        $dao->expectOnce('updatePostAction', array($post_action_id, $new_field_id, $new_value));
        
        $post_action->process($request);
    }
    
    public function itHandlesDeleteRequests() {
        $post_action_id = 9348;
        $transition     = mock('Transition');
        $post_action    = new Transition_PostAction_Field_IntTestVersion();
        $dao            = mock('Transition_PostAction_Field_IntDao');
        $field          = mock('Tracker_FormElement_Field_Integer');
        $value          = 0;
        
        $post_action->__construct($transition, $post_action_id, $field, $value);
        stub($post_action)->getDao()->returns($dao);
        
        $request = aRequest()->with('remove_postaction', array($post_action_id => 1))
                             ->build();
        
        $dao->expectOnce('deletePostAction', array($post_action_id));
        $post_action->process($request);
    }
}
?>
