<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 
require_once(dirname(__FILE__).'/../../../include/workflow/PostAction/Transition_PostActionFactory.class.php');
Mock::generatePartial('Transition_PostActionFactory',
                      'Transition_PostActionFactoryTestVersion',
                      array('getDao')
);
 
require_once(dirname(__FILE__).'/../../../include/workflow/PostAction/Field/dao/Transition_PostAction_Field_DateDao.class.php');
Mock::generate('Transition_PostAction_Field_DateDao');

require_once(dirname(__FILE__).'/../../../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');

Mock::generate('Tracker_FormElement_Field_List_Value');

class Transition_PostActionFactoryTest extends UnitTestCase {
    
    public function testDuplicate() {
        
        $tpaf = new Transition_PostActionFactoryTestVersion();
        $dao  = new MockTransition_PostAction_Field_DateDao();
        $dao->setReturnValue('duplicate', true);
        $tpaf->setReturnReference('getDao', $dao);
        
        $field_date1 = new MockTracker_FormElement_Field_Date();
        $field_date1->setReturnValue('getId', 2066);
        $field_date2 = new MockTracker_FormElement_Field_Date();
        $field_date2->setReturnValue('getId', 2067);
        
        $field_value_analyzed = new MockTracker_FormElement_Field_List_Value();
        $field_value_analyzed->setReturnValue('getId', 2068);
        $field_value_accepted = new MockTracker_FormElement_Field_List_Value();
        $field_value_accepted->setReturnValue('getId', 2069);
        
        $t1  = new Transition(1, 1, $field_value_analyzed, $field_value_accepted);       
        
        $tpa1 = new Transition_PostAction_Field_Date($t1, 1, $field_date1, 1);
        $tpa2 = new Transition_PostAction_Field_Date($t1, 2, $field_date2, 1);
        
        $transitions = array($t1);
             
        $field_mapping = array(
            1  => array('from'=>2066, 'to'=>3066),
            2  => array('from'=>2067, 'to'=>3067),
            3  => array('from'=>2068, 'to'=>3068),
            4  => array('from'=>2069, 'to'=>3069)
        );
        
        $postactions = array($tpa1, $tpa2);
        
        $dao->expectCallCount('duplicate', 2, 'Method getDao should be called 2 times.');
        
        $tpaf->duplicate(1, 2, $postactions, $field_mapping);
    }
}

?>
