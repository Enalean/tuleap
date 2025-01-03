<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

class ArtifactFieldValueListFullRepresentation extends ArtifactFieldValueRepresentationData
{
    /**
     * @var string Type of the field
     */
    public $type;

    /**
     * @var array
     */
    public $values;

    /**
     * @var int[] IDS of the field id
     */
    public $bind_value_ids;

    public function build($id, $type, $label, array $values, array $bind_value_ids)
    {
        $this->field_id       = JsonCast::toInt($id);
        $this->type           = $type;
        $this->label          = $label;
        $this->values         = $values;
        $this->bind_value_ids = $bind_value_ids;
    }
}
