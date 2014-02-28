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
use \UGroup;
use \PFUser;
use \UGroupManager;
use \Tuleap\Project\REST\UserGroupRepresentation;
use \Tuleap\Project\REST\UserRepresentation;
use \Tuleap\REST\Header;
use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;

/**
 * Wrapper for user_groups related REST methods
 */
class UserGroupResource {

    const MAX_LIMIT = 50;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct() {
        $this->ugroup_manager = new UGroupManager();
    }

    /**
     * Get a user_group
     *
     * Get the definition of a given user_group
     *
     * @url GET {id}
     *
     * @param string $id Id of the ugroup (format: projectId_ugroupId)
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

        $this->isGroupViewable($ugroup_id);
        $this->checkUserGroupIdExists($ugroup_id);
        $this->userCanSeeUserGroups($project_id);

        $ugroup                = $this->ugroup_manager->getById($ugroup_id);
        $ugroup_representation = new UserGroupRepresentation();
        $ugroup_representation->build($project_id, $ugroup);
        $this->sendAllowHeadersForUserGroup();

        return $ugroup_representation;
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the ugroup (format: projectId_ugroupId)
     *
     * @access protected
     *
     * @throws 400
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
     * Get users of a user_group
     *
     * Get the users of a given user_group
     *
     * @url GET {id}/users
     *
     * @param string $id Id of the ugroup (format: projectId_ugroupId)
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @access protected
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @throws 406
     *
     * @return Array {@type \Tuleap\Project\REST\UserRepresentation}
     */
    public function getUsers($id, $limit = 10, $offset = 0) {
        $this->checkLimitValueIsAcceptable($limit);
        $this->checkIdIsWellFormed($id);

        list($project_id, $ugroup_id) = explode('_', $id);

        $this->isGroupViewable($ugroup_id);
        $this->checkUserGroupIdExists($ugroup_id);
        $this->userCanSeeUserGroups($project_id);

        $user_group_manager     = new UGroupManager();
        $user_group             = $user_group_manager->getById($ugroup_id);
        $member_representations = array();
        $members                = $this->getUserGroupMembers($user_group, $project_id, $limit, $offset);

        foreach($members as $member) {
            $member_representations[] = $this->getUserRepresentation($member);
        }

        $this->sendPaginationHeaders($limit, $offset, $this->countUserGroupMembers($user_group, $project_id));
        $this->sendAllowHeadersForUserGroup();

        return $member_representations;
    }

    /**
     * @url OPTIONS {id}/users
     *
     * @param int $id Id of the ugroup (format: projectId_ugroupId)
     *
     * @access protected
     *
     * @throws 400
     * @throws 403
     * @throws 404
     */
    public function optionsUsers($id) {
        $this->checkIdIsWellFormed($id);
        list($project_id, $ugroup_id) = explode('_', $id);

        $this->checkUserGroupIdExists($ugroup_id);
        $this->userCanSeeUserGroups($project_id);

        $this->sendAllowHeadersForUserGroup();
    }

    /**
     * Get the members of a group
     *
     * @throws 404
     *
     * @return PFUser[]
     */
    private function getUserGroupMembers(UGroup $user_group, $project_id, $limit, $offset) {
        return $user_group->getStaticOrDynamicMembersPaginated($project_id, $limit, $offset);
    }

    /**
     * Count the members of a group
     *
     * @return int
     */
    private function countUserGroupMembers(UGroup $user_group, $project_id) {
        return $user_group->countStaticOrDynamicMembers($project_id);
    }

    /**
     * Get the UserRepresentation of a user
     *
     * @param PFUser $member
     *
     * @return \Tuleap\Project\REST\UserRepresentation
     */
    private function getUserRepresentation(PFUser $member) {
        $user_representation = new UserRepresentation();
        $user_representation->build($member);

        return $user_representation;
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

    /**
     * @param int $ugroup_id
     *
     * @throws 404
     *
     * @return boolean
     */
    private function isGroupViewable($ugroup_id) {
        $excluded_ugroups_ids = array(UGroup::NONE, UGroup::ANONYMOUS, Ugroup::REGISTERED);

        if (in_array($ugroup_id, $excluded_ugroups_ids)) {
            throw new RestException(404, 'Unable to list the users of this group');
        }

        return true;
    }

    private function sendAllowHeadersForUserGroup() {
        Header::allowOptionsGet();
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * Checks if the limit provided by the request is valid
     *
     * @param int $limit Number of elements displayed per page
     *
     * @return boolean
     *
     * @throws 406
     */

    private function checkLimitValueIsAcceptable($limit) {
        if ($limit > self::MAX_LIMIT) {
             throw new RestException(406, 'limit value is not acceptable');
        }

        return true;
    }
}
