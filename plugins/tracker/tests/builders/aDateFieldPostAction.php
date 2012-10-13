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

require_once dirname(__FILE__).'/../../include/workflow/PostAction/Field/Transition_PostAction_Field_Date.class.php';
require_once 'aTransition.php';
require_once 'aField.php';

function aDateFieldPostAction() {
    return new Test_Transition_PostAction_Field_Date_Builder();
}

class Test_Transition_PostAction_Field_Date_Builder {
    
    private $id         = 0;
    private $value_type = 0;
    
    public function __construct() {
        $this->transition = aTransition()->build();
    }
    
    public function withTransitionId($transition_id) {
        $this->transition = aTransition()->withId($transition_id)->build();
        return $this;
    }
    
    public function withFieldId($field_id) {
        $this->field = aDateField()->withId($field_id)->build();
        return $this;
    }
    
    public function withValueType($value_type) {
        $this->value_type = $value_type;
        return $this;
    }
    
    public function build() {
        return new Transition_PostAction_Field_Date($this->transition, $this->id, $this->field, $this->value_type);
    }
}

?>
