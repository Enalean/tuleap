<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tracker_FormElement_Field_List_Value;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\REST\JsonCast;

class FieldListBindUGroupValueRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var MinimalUserGroupRepresentation
     */
    public $ugroup_reference;

    public function build(Tracker_FormElement_Field_List_Value $value, MinimalUserGroupRepresentation $ugroup_representation)
    {
        $this->id               = JsonCast::toInt($value->getId());
        $this->label            = $value->getAPIValue();
        $this->ugroup_reference = $ugroup_representation;
    }
}
