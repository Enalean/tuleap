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

function aBindStatic()
{
    return new Test_Tracker_FormElement_List_Bind_Static_Builder('Tracker_FormElement_Field_List_Bind_Static');
}

class Test_Tracker_FormElement_List_Bind_Static_Builder
{
    private $values = null;
    private $field  = null;

    public function withField(Tracker_FormElement_Field_List $field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * @param Tracker_FormElement_Field_List_Bind_StaticValue[] $values
     * @return Test_Tracker_FormElement_List_Bind_Static_Builder
     */
    public function withValues(array $values)
    {
        $this->values = $values;
        return $this;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_Static
     */
    public function build()
    {
        $is_rank_alpha = $default_values = $decorators = null;
        $object = new Tracker_FormElement_Field_List_Bind_Static($this->field, $is_rank_alpha, $this->values, $default_values, $decorators);
        return $object;
    }
}
