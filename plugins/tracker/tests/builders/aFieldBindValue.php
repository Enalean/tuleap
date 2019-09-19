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

function aFieldListStaticValue()
{
    return new Test_Tracker_FormElement_Field_List_BindValue_Builder('Tracker_FormElement_Field_List_Bind_StaticValue');
}

class Test_Tracker_FormElement_Field_List_BindValue_Builder
{
    private $name;
    private $id;
    private $label;
    private $description;
    private $rank;
    private $is_hidden;

    public function __construct($klass)
    {
        $this->name = $klass;
    }

    public function withId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function withLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function build()
    {
        $klass  = $this->name;
        $object = new $klass($this->id, $this->label, $this->description, $this->rank, $this->is_hidden);
        return $object;
    }
}
