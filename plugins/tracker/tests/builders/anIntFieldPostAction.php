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

require_once dirname(__FILE__).'/../../include/workflow/PostAction/Field/Transition_PostAction_Field_Int.class.php';
require_once 'aTransition.php';
require_once 'aField.php';

function anIntFieldPostAction() {
    return new Test_Transition_PostAction_Field_Int_Builder();
}

class Test_Transition_PostAction_Field_Int_Builder {
    
    private $id;
    
    public function __construct() {
        $this->transition = aTransition();
        $this->field      = anIntegerField();
    }
    
    public function withTransitionId($transition_id) {
        $this->transition->withId($transition_id);
        return $this;
    }
    
    public function withFieldId($field_id) {
        $this->field->withId($field_id);
        return $this;
    }
    
    public function withValue($value) {
        $this->value = $value;
        return $this;
    }
    
    public function build() {
        return new Transition_PostAction_Field_Int($this->transition->build(),
                                                   $this->id,
                                                   $this->field->build(),
                                                   $this->value);
    }
}

?>
