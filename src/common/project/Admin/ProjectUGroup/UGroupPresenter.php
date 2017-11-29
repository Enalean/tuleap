<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project\Admin\ProjectUGroup;

use Project;
use ProjectUGroup;

class UGroupPresenter extends MinimalUGroupPresenter
{
    public $nb_members;
    public $can_be_deleted;

    public function __construct(Project $project, ProjectUGroup $ugroup, $can_be_deleted)
    {
        parent::__construct($ugroup);

        $this->nb_members     = $ugroup->countStaticOrDynamicMembers($project->getID());
        $this->can_be_deleted = $can_be_deleted;
    }
}
