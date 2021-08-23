<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\ProjectBackground;

class ProjectBackgroundUpdater
{
    /**
     * @var ProjectBackgroundDao
     */
    private $project_background_dao;

    public function __construct(ProjectBackgroundDao $project_background_dao)
    {
        $this->project_background_dao = $project_background_dao;
    }

    public function updateProjectBackgroundImage(
        UserCanModifyProjectBackgroundPermission $permission,
        ProjectBackgroundName $new_background_name
    ): void {
        $this->project_background_dao->setBackgroundImageByProjectID(
            (int) $permission->getProject()->getID(),
            $new_background_name->getIdentifier()
        );
    }

    public function updateProjectBackgroundColor(
        UserCanModifyProjectBackgroundPermission $permission,
        ProjectBackgroundColorName $color_name
    ): void {
        $this->project_background_dao->setBackgroundColorByProjectID(
            (int) $permission->getProject()->getID(),
            $color_name->getColorName()
        );
    }

    public function deleteProjectBackground(UserCanModifyProjectBackgroundPermission $permission): void
    {
        $this->project_background_dao->deleteBackgroundByProjectID((int) $permission->getProject()->getID());
    }
}
