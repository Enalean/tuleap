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

namespace Tuleap\Widget\ProjectMembers;

use Project;
use UGroupUserDao;

class AdministratorPresenterBuilder
{
    /** @var UGroupUserDao */
    private $dao;
    /** @var \UserManager */
    private $user_manager;
    /** @var \UserHelper */
    private $user_helper;

    public function __construct(UGroupUserDao $dao, \UserManager $user_manager, \UserHelper $user_helper)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
        $this->user_helper  = $user_helper;
    }

    /**
     * @return AdministratorPresenter[]
     */
    public function build(Project $project)
    {
        $presenters = [];
        $rows       = $this->dao->searchUserByDynamicUGroupId(\ProjectUGroup::PROJECT_ADMIN, $project->getID());
        foreach ($rows as $row) {
            $administrator = $this->user_manager->getUserById($row['user_id']);
            if ($administrator) {
                $administrator_presenter = new AdministratorPresenter($this->user_helper);
                $administrator_presenter->build($administrator);
                $presenters[] = $administrator_presenter;
            }
        }

        return $presenters;
    }
}
