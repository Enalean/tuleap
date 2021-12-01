<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use Project;
use ProjectUGroup;

class PermissionPerGroupLoadAllButtonPresenter
{
    /**
     * @var int|string
     */
    public $ugroup_id;

    /**
     * @var string
     */
    public $selected_ugroup_name;

    /**
     * @var int
     */
    public $project_id;

    public function __construct(
        Project $project,
        ?ProjectUGroup $ugroup = null,
    ) {
        $this->project_id = $project->getID();
        $this->ugroup_id  = ($ugroup)
            ? $ugroup->getId()
            : '';

        $this->selected_ugroup_name = ($ugroup)
            ? $ugroup->getTranslatedName()
            : '';
    }
}
