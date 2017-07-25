<?php
/**
 * Copyright (c) Enalean, 2014 - 2015. All Rights Reserved.
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
use \ProjectUGroup;
use \PFUser;
use \UGroupManager;
use \URLVerification;
use \Tuleap\Project\REST\UserGroupRepresentation;
use \Tuleap\User\REST\UserRepresentation;
use \Tuleap\REST\Header;
use \Tuleap\REST\ProjectAuthorization;
use \Luracast\Restler\RestException;
use Tuleap\REST\AuthenticatedResource;

/**
 * Wrapper for user_groups related REST methods
 */
class UserGroupResource extends AuthenticatedResource {

    const MAX_LIMIT = 50;

    const KEY_ID      = 'id';
    const USERNAME_ID = 'username';
    const EMAIL_ID    = 'email';
    const LDAP_ID_ID  = 'ldap_id';

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct() {
        $this->ugroup_manager  = new UGroupManager();
        $this->user_manager    = UserManager::instance();
        $this->project_manager = ProjectManager::instance();
    }

    /**
     * Get a user_group
     *
     * Get the definition of a given user_group
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     *
     * @throws 400
     * @throws 403
     * @throws 404
     *
     * @return \Tuleap\Project\REST\UserGroupRepresentation
     */
    public function getId($id) {
        $this->checkAccess();

        $ugroup     = $this->getExistingUserGroup($id);
        $project_id = $ugroup->getProjectId();

        if ($project_id) {
            $this->userCanSeeUserGroups($project_id);
        }

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
     * @throws 400
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        $this->sendAllowHeadersForUserGroup();
    }

    /**
     * Get users of a user_group
     *
     * Get the users of a given user_group
     *
     * @url GET {id}/users
     * @access protected
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @throws 400
     * @throws 403
     * @throws 404
     * @throws 406
     *
     * @return Array {@type \Tuleap\User\REST\UserRepresentation}
     */
    protected function getUsers($id, $limit = 10, $offset = 0) {
        $this->checkLimitValueIsAcceptable($limit);

        $user_group = $this->getExistingUserGroup($id);
        $this->checkGroupIsViewable($user_group->getId());
        $project_id = $user_group->getProjectId();
        $this->userCanSeeUserGroupMembers($project_id);

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
     * Define users of a user_group
     *
     * Define the users of a given user_group
     * <br><br>
     * Notes on the user reference format. It can be:
     * <ul>
     * <li>* {"id": user_id}</li>
     * <li>* {"username": user_name}</li>
     * <li>* {"email": user_email}</li>
     * <li>* {"ldap_id": user_ldap_id}</li>
     * </ul>
     * <br><br>
     * <p>Concerning the group <b>project members</b>, please note:</p>
     * <ul>
     * <li>* Suspended users will be removed from the group if they are not provided</li>
     * <li>* Suspended users will not be added to the group, even if they are provided</li>
     * <li>* Project admins will not be removed from the group, even if they are provided</li>
     * </ul>
     *
     * @url PUT {id}/users
     *
     * @access protected
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     * @param array $user_references {@from body}
     *
     * @throws 400
     * @throws 404
     */
    protected function putUsers($id, array $user_references) {
        $this->checkAccess();

        $user_group = $this->getExistingUserGroup($id);
        $this->checkUgroupValidity($user_group);

        $project_id = $user_group->getProjectId();
        $this->userCanSeeUserGroupMembers($project_id);

        $this->checkKeysValidity($user_references);

        $users_from_references = $this->getMembersFromReferences($user_references);

        try {
            $this->ugroup_manager->syncUgroupMembers($user_group, $users_from_references);
        } catch (Exception $exception) {
            throw new RestException(500, "An error occured while setting members in ugroup");
        }
    }

    private function checkUgroupValidity(ProjectUGroup $user_group) {
        if (! $user_group->isStatic() && $user_group->getId() != ProjectUGroup::PROJECT_MEMBERS) {
            throw new RestException(400, "Only project members can be taken into account for the dynamic user groups");
        }

        if ($user_group->getSourceGroup() !== null) {
            throw new RestException(400, "Ugroup is bound to a source group");
        }

        $this->checkGroupIsViewable($user_group->getId());
    }

    /**
     * @return PFUser
     */
    private function getUserRegardingKey($key, $value) {
        if ($key === self::KEY_ID) {
            return $this->user_manager->getUserById($value);
        } elseif ($key === self::USERNAME_ID) {
            return $this->user_manager->getUserByUserName($value);
        } elseif ($key === self::EMAIL_ID) {
            $users = $this->user_manager->getAllUsersByEmail($value);

            if (count($users) > 1 ) {
                throw new RestException(400, "More than one user use the email address $value");
            } elseif (count($users) === 0) {
                return null;
            }

            return $users[0];
        } elseif ($key === self::LDAP_ID_ID) {
            $identifier = 'ldapId:' . $value;

            return $this->user_manager->getUserByIdentifier($identifier);
        }
    }

    /**
     * @return array
     * @throws RestException
     */
    private function getMembersFromReferences(array $user_references) {
        $users_to_add = array();

        foreach ($user_references as $user_reference) {
            $key   = key($user_reference);
            $value = $user_reference[$key];

            $user = $this->getUserRegardingKey($key, $value);

            if (! $user) {
                throw new RestException(400, "User with reference $key: $value not known");
            }

            $users_to_add[] = $user;
        }

        return $users_to_add;
    }

    private function checkKeysValidity(array $user_references) {
        if (empty($user_references)) {
            return true;
        }

        $first_key          = null;
        $available_keywords = array(
            self::KEY_ID,
            self::USERNAME_ID,
            self::EMAIL_ID,
            self::LDAP_ID_ID,
        );

        foreach ($user_references as $user_reference) {

            if (count(array_keys($user_reference)) > 1) {
                throw new RestException(400, "Only one key can be passed in the representation");
            }

            $key = key($user_reference);

            if (! in_array($key, $available_keywords)) {
                throw new RestException(400, "key $key not known");
            }

            if ($first_key === null) {
                $first_key = $key;
            } elseif ($first_key !== $key) {
                throw new RestException(400, "references have to use the same type");
            }
        }

        return true;
    }

    /**
     * @url OPTIONS {id}/users
     *
     * @param int $id Id of the ugroup (format: projectId_ugroupId)
     */
    public function optionsUsers($id) {
        $this->sendAllowHeadersForUserGroup();
    }

    /**
     * Get the members of a group
     *
     * @throws 404
     *
     * @return PFUser[]
     */
    private function getUserGroupMembers(ProjectUGroup $user_group, $project_id, $limit, $offset) {
        return $user_group->getStaticOrDynamicMembersPaginated($project_id, $limit, $offset);
    }

    /**
     * Count the members of a group
     *
     * @return int
     */
    private function countUserGroupMembers(ProjectUGroup $user_group, $project_id) {
        return $user_group->countStaticOrDynamicMembers($project_id);
    }

    /**
     * Get the UserRepresentation of a user
     *
     * @param PFUser $member
     *
     * @return \Tuleap\User\REST\UserRepresentation
     */
    private function getUserRepresentation(PFUser $member) {
        $user_representation = new UserRepresentation();
        $user_representation->build($member);

        return $user_representation;
    }

    /**
     * Checks if the given id is appropriate (format: projectId_ugroupId or format: ugroupId)
     *
     * @param string $id Id of the ugroup
     *
     * @return boolean
     *
     * @throws 400
     */
    private function checkIdIsAppropriate($id) {
        try {
            UserGroupRepresentation::checkRESTIdIsAppropriate($id);
        } catch (\Exception $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    /**
     * Checks if the given user group exists
     *
     * @param int $id
     *
     * @return ProjectUGroup
     *
     * @throws 404
     */
    private function getExistingUserGroup($id) {
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
     * @throws 403
     * @throws 404
     *
     * @return boolean
     */
    private function userCanSeeUserGroups($project_id) {
        $project      = $this->project_manager->getProject($project_id);
        $user         = $this->user_manager->getCurrentUser();
        ProjectAuthorization::canUserAccessUserGroupInfo($user, $project, new URLVerification());

        return true;
    }

    /**
     * @throws 403
     * @throws 404
     *
     * @return boolean
     */
    private function userCanSeeUserGroupMembers($project_id) {
        $project      = $this->project_manager->getProject($project_id);
        $user         = $this->user_manager->getCurrentUser();
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
    private function checkGroupIsViewable($ugroup_id) {
        if (in_array($ugroup_id, ProjectUGroup::$forge_user_groups)) {
            throw new RestException(404, 'Unable to list the users of this group');
        }

        return true;
    }

    private function sendAllowHeadersForUserGroup() {
        Header::allowOptionsGetPut();
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
