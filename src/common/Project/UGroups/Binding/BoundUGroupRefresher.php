<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Binding;

use Exception;
use LogicException;
use ProjectUGroup;

class BoundUGroupRefresher
{
    /** @var \UGroupManager */
    private $ugroup_manager;
    /** @var \UGroupUserDao */
    private $ugroup_user_dao;

    public function __construct(\UGroupManager $ugroup_manager, \UGroupUserDao $ugroup_user_dao)
    {
        $this->ugroup_manager  = $ugroup_manager;
        $this->ugroup_user_dao = $ugroup_user_dao;
    }

    /**
     * @throws \Exception
     */
    public function refresh(ProjectUGroup $source, ProjectUGroup $destination): void
    {
        $destination_id = $destination->getId();
        if (! $this->ugroup_manager->isUpdateUsersAllowed($destination_id)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('project_ugroup_binding', 'update_user_not_allowed', [$destination_id])
            );
            throw new Exception(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'add_error')
            );
        }
        try {
            $this->clearMembers($destination);
            $this->duplicateMembers($source, $destination);
        } catch (LogicException $e) {
            //re-throw exception
            throw new Exception($e->getMessage());
        }
    }

    private function clearMembers(ProjectUGroup $ugroup): void
    {
        $ugroup_id = $ugroup->getId();
        if ($this->ugroup_user_dao->resetUgroupUserList($ugroup_id) === false) {
            throw new LogicException(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'reset_error', [$ugroup_id])
            );
        }
    }

    private function duplicateMembers(ProjectUGroup $source, ProjectUGroup $destination): void
    {
        $source_id      = $source->getId();
        $destination_id = $destination->getId();
        if ($this->ugroup_user_dao->cloneUgroup($source_id, $destination_id) === false) {
            throw new LogicException(
                $GLOBALS['Language']->getText('project_ugroup_binding', 'clone_error', [$destination_id])
            );
        }
    }
}
