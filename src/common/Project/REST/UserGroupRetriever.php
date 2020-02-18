<?php
/**
 *  Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Luracast\Restler\RestException;
use ProjectUGroup;
use UGroupManager;

class UserGroupRetriever
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    /**
     * @return ProjectUGroup
     *
     * @throws RestException 404
     */
    public function getExistingUserGroup(string $id)
    {
        $this->checkIdIsAppropriate($id);

        $values        = UserGroupRepresentation::getProjectAndUserGroupFromRESTId($id);
        $user_group_id = $values['user_group_id'];

        $user_group = $this->ugroup_manager->getById($user_group_id);

        if ($user_group->getId() === 0) {
            throw new RestException(404, 'User Group does not exist');
        }

        if (! $user_group->isStatic()) {
            $user_group->setProjectId($values['project_id']);
        }

        if ($user_group->isStatic() && $values['project_id'] && $values['project_id'] != $user_group->getProjectId()) {
            throw new RestException(404, 'User Group does not exist in project');
        }

        return $user_group;
    }

    /**
     * @throws RestException 400
     */
    private function checkIdIsAppropriate($id): void
    {
        try {
            UserGroupRepresentation::checkRESTIdIsAppropriate($id);
        } catch (\Exception $e) {
            throw new RestException(400, $e->getMessage());
        }
    }
}
