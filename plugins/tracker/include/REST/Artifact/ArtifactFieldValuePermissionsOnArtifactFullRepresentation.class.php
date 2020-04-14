<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

class ArtifactFieldValuePermissionsOnArtifactFullRepresentation extends ArtifactFieldValueRepresentationData
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
     * @var string[]
     */
    public $granted_groups = array();

    /**
     * @var string[]
     */
    public $granted_groups_ids = array();

    public function build($id, $type, $label, array $granted_groups, array $granted_groups_ids)
    {
        $this->field_id       = JsonCast::toInt($id);
        $this->type           = $type;
        $this->label          = $label;
        $this->granted_groups = $granted_groups;
        $this->granted_groups_ids = $granted_groups_ids;
    }
}
