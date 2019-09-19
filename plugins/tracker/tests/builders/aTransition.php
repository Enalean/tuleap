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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html
require_once __DIR__.'/../bootstrap.php';

function aTransition()
{
    return new Test_Transition_Builder();
}

class Test_Transition_Builder
{

    /**
     * @var int
     */
    private $transition_id = 0;

    /**
     * @var int
     */
    private $workflow_id = 0;

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    private $from_field_value;

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    private $to_field_value;

    public function withId($id)
    {
        $this->transition_id = $id;
        return $this;
    }

    public function fromFieldValueId($id)
    {
        $this->from_field_value = mock('Tracker_FormElement_Field_List_Value');
        stub($this->from_field_value)->getId()->returns($id);
        return $this;
    }

    public function toFieldValueId($id)
    {
        $this->to_field_value = mock('Tracker_FormElement_Field_List_Value');
        stub($this->to_field_value)->getId()->returns($id);
        return $this;
    }

    public function build()
    {
        return new Transition(
            $this->transition_id,
            $this->workflow_id,
            $this->from_field_value,
            $this->to_field_value
        );
    }
}
