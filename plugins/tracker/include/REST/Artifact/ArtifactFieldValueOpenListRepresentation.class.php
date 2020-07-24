<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class ArtifactFieldValueOpenListRepresentation extends ArtifactFieldValueRepresentationData
{
    /**
     * @var mixed
     */
    public $bind_value_objects = [];

    /**
     * @deprecated
     * @var string[]
     */
    public $bind_value_ids = [];

    public $bind_type;

    public function build($id, $label, $bind_type, array $bind_value_objects, array $bind_value_ids)
    {
        $this->field_id           = JsonCast::toInt($id);
        $this->label              = $label;
        $this->bind_type          = $bind_type;
        $this->bind_value_objects = $bind_value_objects;
        $this->bind_value_ids     = $bind_value_ids;
    }
}
