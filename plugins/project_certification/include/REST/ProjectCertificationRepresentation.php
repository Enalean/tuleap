<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\ProjectCertification\REST;

use Tuleap\User\REST\MinimalUserRepresentation;

class ProjectCertificationRepresentation
{
    /**
     * @var MinimalUserRepresentation {@type \Tuleap\User\REST\MinimalUserRepresentation}
     */
    public $project_owner;

    public function build(\PFUser $project_owner = null)
    {
        if ($project_owner !== null) {
            $user_representation = new MinimalUserRepresentation();
            $user_representation->build($project_owner);
            $this->project_owner = $user_representation;
        }
    }
}
