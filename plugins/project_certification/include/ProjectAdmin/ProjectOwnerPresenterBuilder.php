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

namespace Tuleap\ProjectCertification\ProjectAdmin;

use Project;
use Tuleap\ProjectCertification\ProjectOwner\ProjectOwnerDAO;
use UserManager;

class ProjectOwnerPresenterBuilder
{
    /** @var ProjectOwnerDAO */
    private $dao;
    /** @var UserManager */
    private $user_manager;
    /** @var \UserHelper */
    private $user_helper;
    /** @var \BaseLanguage */
    private $language;

    public function __construct(
        ProjectOwnerDAO $dao,
        UserManager $user_manager,
        \UserHelper $user_helper,
        \BaseLanguage $language
    ) {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
        $this->user_helper  = $user_helper;
        $this->language     = $language;
    }

    public function build(Project $project)
    {
        $row = $this->dao->searchByProjectID($project->getID());
        $project_owner = $this->user_manager->getUserById($row['user_id']);

        $presenter = new ProjectOwnerPresenter($this->user_helper, $this->language);
        $presenter->build($project_owner);
        return $presenter;
    }
}
