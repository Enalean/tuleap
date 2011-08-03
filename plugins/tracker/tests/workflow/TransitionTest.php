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

require_once(dirname(__FILE__).'/../../include/workflow/Transition.class.php');


class TransitionTest extends UnitTestCase {
    
    public function testEquals() {
        
         $field_value_new = array('id' => 2066,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'New',
                                                           'description' => 'The bug has been submitted',
                                                           'rank' => '10');
        $field_value_analyzed = array('id' => 2067,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Analyzed',
                                                           'description' => 'The bug is analyzed',
                                                           'rank' => '20');
        $field_value_accepted = array('id' => 2068,
                                                           'old_id' => null,
                                                           'field_id' => 2707,
                                                           'value' => 'Accepted',
                                                           'description' => 'The bug is accepted',
                                                           'rank' => '30');
        
        $t1 = new Transition (1, 2, $field_value_new, $field_value_analyzed);
        $t2 = new Transition (1, 2, $field_value_analyzed, $field_value_accepted);
        $t3 = new Transition (1, 2, $field_value_analyzed, $field_value_new);
        $t4 = new Transition (1, 2, $field_value_new, $field_value_analyzed); // equals $t1
        
        $this->assertTrue($t1->equals($t1));
        $this->assertTrue($t2->equals($t2));
        $this->assertTrue($t3->equals($t3));
        
        $this->assertFalse($t1->equals($t2));
        $this->assertFalse($t2->equals($t1));
        $this->assertFalse($t2->equals($t3));
        
    }
    
}
?>