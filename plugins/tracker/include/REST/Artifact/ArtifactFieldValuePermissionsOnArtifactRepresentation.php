<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class ArtifactFieldValuePermissionsOnArtifactRepresentation extends ArtifactFieldValueRepresentationData
{
    /**
     * @var string[]
     */
    public $granted_groups = [];

    /**
     * @var Tuleap\Project\REST\UserGroupRepresentation[]
     */
    public $granted_groups_details = [];

    public function build($id, $label, array $granted_groups, array $granted_groups_details)
    {
        $this->field_id               = JsonCast::toInt($id);
        $this->label                  = $label;
        $this->granted_groups         = $granted_groups;
        $this->granted_groups_details = $granted_groups_details;
    }
}
