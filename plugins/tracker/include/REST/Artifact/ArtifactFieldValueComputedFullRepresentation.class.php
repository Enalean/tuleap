<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\REST\JsonCast;

class ArtifactFieldComputedValueFullRepresentation
{
    /**
     * @var int ID of the field
     */
    public $field_id;

    /**
     * @var string Type of the field
     */
    public $type;

    /**
     * @var string Label of the field
     */
    public $label;

    /**
     * @var bool
     */
    public $is_autocomputed;

    /**
     * @var float|null
     */
    public $value;

    /**
     * @var float
     */
    public $manual_value;

    public function build($id, $type, $label, $is_autocomputed, $autocomputed_value, $manual_value)
    {
        $this->field_id        = JsonCast::toInt($id);
        $this->type            = $type;
        $this->label           = $label;
        $this->is_autocomputed = JsonCast::toBoolean($is_autocomputed);
        $this->value           = JsonCast::toFloat($autocomputed_value);
        $this->manual_value    = JsonCast::toFloat($manual_value);
    }
}
