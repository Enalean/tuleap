<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class ArtifactFieldValueFileRepresentation
{
    /**
     * @var int ID of the field
     */
    public $field_id;

    /**
     * @var string Label of the field
     */
    public $label;

    /**
     * @var Tuleap\Tracker\REST\Artifact\FileInfoRepresentation[]
     */
    public $file_descriptions = [];

    public function build($id, $label, array $values)
    {
        $this->field_id          = JsonCast::toInt($id);
        $this->label             = $label;
        $this->file_descriptions = $values;
    }
}
