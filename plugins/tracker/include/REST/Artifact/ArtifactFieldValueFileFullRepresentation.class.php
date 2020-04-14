<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ArtifactFieldValueFileFullRepresentation extends ArtifactFieldValueRepresentationData
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
     * @var Tuleap\Tracker\REST\Artifact\FileInfoRepresentation[]
     */
    public $file_descriptions = array();

    public function build($id, $type, $label, array $values)
    {
        $this->field_id          = JsonCast::toInt($id);
        $this->type              = $type;
        $this->label             = $label;
        $this->file_descriptions = $values;
    }
}
