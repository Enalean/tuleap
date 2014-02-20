<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Project\REST\v1;

use \ProjectManager;
use \UserManager;
use \UGroupManager;
use \Tuleap\Project\REST\UserGroupRepresentation;
use \Tuleap\REST\Header;
use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;

/**
 * Wrapper for user_groups related REST methods
 */
class UserGroupResource {

    /**
     * Get a user_group
     *
     * Get the definition of a given user_group
     *
     * @url GET {id}
     *
     * @param string $id  Id of the ugroup (format: projectId_ugroupId)
     *
     * @access protected
     *
     * @throws 400
     * @throws 403
     * @throws 404
     *
     * @return \Tuleap\Project\REST\UserGroupRepresentation
     */
    protected function getId($id) {
        $this->checkIdIsWellFormed($id);
        list($project_id, $ugroup_id) = explode('_', $id);
        $this->checkUserGroupIdExists($ugroup_id);

        $this->userCanSeeUserGroups($project_id);
        $ugroup_representation = new UserGroupRepresentation();
        $ugroup_representation->build($project_id, $ugroup_id);
        $this->sendAllowHeadersForUserGroup();

        return $ugroup_representation;
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the project
     *
     * @access protected
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->checkIdIsWellFormed($id);
        list($project_id, $ugroup_id) = explode('_', $id);

        $this->userCanSeeUserGroups($project_id);
        $this->sendAllowHeadersForUserGroup();
    }

    /**
     * Checks if the given id is well formed (format: projectId_ugroupId)
     *
     * @param string $id Id of the ugroup (format: projectId_ugroupId)
     *
     * @return boolean
     *
     * @throws 400
     */
    private function checkIdIsWellFormed($id) {
        $regexp = '/^[0-9]+_[0-9]+$/';

        if (! preg_match($regexp, $id)) {
            throw new RestException(400, 'Invalid id format, format must be: projectId_ugroupId');
        }

        return true;
    }

    /**
     * Checks if the given user group exists
     *
     * @param int $user_group_id
     *
     * @return boolean
     *
     * @throws 404
     */
    private function checkUserGroupIdExists($user_group_id) {
        $user_group_manager = new UGroupManager();
        $user_group         = $user_group_manager->getById($user_group_id);

        if ($user_group->getId() != $user_group_id) {
            throw new RestException(404, 'Given user group id does not exist');
        }

        return true;
    }

    private function sendAllowHeadersForUserGroup() {
        Header::allowOptionsGet();
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return boolean
     */
    private function userCanSeeUserGroups($project_id) {
        $user_manager = UserManager::instance();
        $project      = ProjectManager::instance()->getProject($project_id);
        $user         = $user_manager->getCurrentUser();
        ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);

        return true;
    }

}
