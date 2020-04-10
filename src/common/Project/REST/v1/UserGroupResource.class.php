<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\Admin\ProjectUGroup\CannotCreateUGroupException;
use Tuleap\Project\Admin\ProjectUGroup\CannotRemoveUserMembershipToUserGroupException;
use Tuleap\Project\Admin\ProjectUGroup\UserIsUGroupMemberChecker;
use Tuleap\Project\REST\UserGroupPOSTRepresentation;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Project\REST\UserRESTReferenceRepresentation;
use Tuleap\Project\REST\UserRESTReferenceRetriever;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\MissingMandatoryParameterException;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\QueryParameterParser;
use Tuleap\User\REST\UserRepresentation;
use UGroupManager;
use URLVerification;
use UserManager;

/**
 * Wrapper for user_groups related REST methods
 */
class UserGroupResource extends AuthenticatedResource
{

    public const MAX_LIMIT = 50;
    /**
     * @var UserIsUGroupMemberChecker
     */
    private $ugroup_member_checker;
    /**
     * @var QueryParameterParser
     */
    private $query_parser;

    /**
     * @var UserGroupRetriever
     */
    private $user_group_retriever;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct()
    {
        $this->ugroup_manager        = new UGroupManager();
        $this->user_manager          = UserManager::instance();
        $this->project_manager       = ProjectManager::instance();
        $this->user_group_retriever  = new UserGroupRetriever($this->ugroup_manager);
        $this->query_parser          = new QueryParameterParser(new JsonDecoder());
        $this->ugroup_member_checker = new UserIsUGroupMemberChecker(
            new UserPermissionsDao(),
            new \User_ForgeUserGroupUsersDao()
        );
    }

    /**
     * Get a user_group
     *
     * Get the definition of a given user_group
     *
     * @url GET {id}
     * @access hybrid
     * @oauth2-scope read:user_membership
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return \Tuleap\Project\REST\UserGroupRepresentation
     */
    public function getId($id)
    {
        $this->checkAccess();

        $ugroup     = $this->user_group_retriever->getExistingUserGroup($id);
        $project_id = $ugroup->getProjectId();

        if ($project_id) {
            ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
                $this->user_manager->getCurrentUser(),
                $ugroup->getProject()
            );
            $this->userCanSeeUserGroups($project_id);
        }

        $ugroup_representation = new UserGroupRepresentation();
        $ugroup_representation->build((int) $project_id, $ugroup);
        $this->sendAllowHeadersForUserGroupId();

        return $ugroup_representation;
    }

    /**
     * @url OPTIONS {id}
     *
     * @param string $id Id of the ugroup (format: projectId_ugroupId)
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    public function optionsId($id)
    {
        $this->sendAllowHeadersForUserGroupId();
    }

    /**
     * Get users of a user_group
     *
     * Get the users of a given user_group
     *
     * <br>
     * <br>
     * ?query is optional. When filled, it is a json object:
     * <ul>
     *   <li>With a property "identifier" to search if user_name is present in project_members.
     *     If user present it will retrieve its representation.
     *     Example: <pre>{"identifier": "my_user_name"}</pre>
     *   </li>
     * </ul>
     *
     * @url GET {id}/users
     * @access protected
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     * @param int    $limit Number of elements displayed per page
     * @param int    $offset Position of the first element to display
     * @param string $query User name to look for
     *
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     * @throws RestException 406
     *
     * @return array {@type \Tuleap\User\REST\UserRepresentation}
     */
    protected function getUsers($id, $limit = 10, $offset = 0, $query = null)
    {
        $this->checkLimitValueIsAcceptable($limit);

        $user_group = $this->user_group_retriever->getExistingUserGroup($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsOnlySiteAdminToAccessIt(
            $this->user_manager->getCurrentUser(),
            $user_group->getProject()
        );

        $this->checkGroupIsViewable($user_group->getId());
        $project_id = $user_group->getProjectId();
        $this->userCanSeeUserGroupMembers($user_group);

        $member_representations = array();

        try {
            $identifier = $this->query_parser->getString($query, 'identifier');
        } catch (MissingMandatoryParameterException $e) {
            $identifier = null;
        }

        if ($identifier === null) {
            $members = $this->getUserGroupMembers($user_group, $project_id, $limit, $offset);

            foreach ($members as $member) {
                $member_representations[] = $this->getUserRepresentation($member);
            }
            $this->sendPaginationHeaders($limit, $offset, $this->countUserGroupMembers($user_group, $project_id));
        } else {
            $member = $this->getUGroupMemberByIdentifier($identifier, $user_group);

            $nb_member = 0;
            if ($member !== null) {
                $member_representations   = array_slice(array($member), $offset, $limit);
                $nb_member                = 1;
            }
            $this->sendPaginationHeaders($limit, $offset, $nb_member);
        }

        $this->sendAllowHeadersForUserGroupId();

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
     * <li>Suspended users will be removed from the group if they are not provided</li>
     * <li>Suspended users will not be added to the group, even if they are provided</li>
     * <li>Project admins will not be removed from the group, even if they are provided</li>
     * </ul>
     *
     * <p>Concerning the group <b>project administrators</b>, please note that the users will be added
     * if needed to the project members group.
     * </p>
     *
     * @url PUT {id}/users
     *
     * @access protected
     *
     * @param string $id Id of the ugroup This should be one of two formats<br>
     * - format: projectId_ugroupId for dynamic project user groups (project members...)<br>
     * - format: ugroupId for all other groups (registered users, custom groups, ...)
     *
     * @param array $user_references {@from body}
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     */
    protected function putUsers($id, array $user_references)
    {
        $this->checkAccess();

        $user_group = $this->user_group_retriever->getExistingUserGroup($id);

        ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
            $user_group->getProject()
        );

        $this->checkUgroupValidity($user_group);
        $this->userCanSeeUserGroupMembers($user_group);

        /*
         * This REST endpoint can takes the same data as input with two different bodies.
         * This body:
         *     {
         *          "user_references": [
         *              {"user_id": 102},
         *              {"user_id": 103}
         *          ]
         *     }
         * is equivalent to this one:
         *     [
         *          {"user_id": 102},
         *          {"user_id": 103}
         *     ]
         *
         * As this behavior might be used and expected by end users, this endpoint cannot
         * take another parameter in its body or use a dedicated class to represent its body
         * without introducing a breaking change.
         *
         * A side effect is that the @type annotation cannot be used on the $user_references
         * param without breaking the requests generated from the API Explorer. To keep everything
         * in a working state we "cast" the non-typed user references into the expected representations.
         * This issue with the @type annotation seems fixable with a moderate effort: only the API Explorer
         * exhibits an issue, Restler is capable of parsing a request with a body formed like one of the
         * two examples.
         */
        $user_references_representations = [];
        foreach ($user_references as $user_reference) {
            if (! is_array($user_reference)) {
                throw new RestException(400, 'Invalid format provided for "user_references"');
            }

            $user_references_representations[] = UserRESTReferenceRepresentation::buildFromArray($user_reference);
        }

        $users_from_references = $this->getMembersFromReferences(...$user_references_representations);

        try {
            $this->ugroup_manager->syncUgroupMembers($user_group, $users_from_references);
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted | CannotRemoveUserMembershipToUserGroupException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function checkUgroupValidity(ProjectUGroup $user_group)
    {
        if (
            ! $user_group->isStatic() &&
            (int) $user_group->getId() !== ProjectUGroup::PROJECT_MEMBERS &&
            (int) $user_group->getId() !== ProjectUGroup::PROJECT_ADMIN
        ) {
            throw new RestException(
                400,
                'Only project members or administrators can be taken into account for the dynamic user groups'
            );
        }

        if ($user_group->getSourceGroup() !== null) {
            throw new RestException(400, "Ugroup is bound to a source group");
        }

        $this->checkGroupIsViewable($user_group->getId());
    }

    /**
     * @return array
     * @throws RestException
     */
    private function getMembersFromReferences(UserRESTReferenceRepresentation ...$representations)
    {
        $user_retriever_rest_reference = new UserRESTReferenceRetriever($this->user_manager);
        $users_to_add                  = [];

        $this->checkKeysValidity(...$representations);

        foreach ($representations as $representation) {
            $user = $user_retriever_rest_reference->getUserFromReference($representation);

            if (! $user) {
                throw new RestException(400, "User with reference $representation not known");
            }

            $users_to_add[] = $user;
        }

        return $users_to_add;
    }

    /**
     * @throws RestException
     */
    private function checkKeysValidity(UserRESTReferenceRepresentation ...$representations)
    {
        $first_key = null;
        foreach ($representations as $representation) {
            foreach ($representation as $key => $value) {
                if ($value === null) {
                    continue;
                }
                if ($first_key === null) {
                    $first_key = $key;
                } elseif ($first_key !== $key) {
                    throw new RestException(400, 'References have to use the same type');
                }
            }
        }
    }

    /**
     * @url OPTIONS {id}/users
     *
     * @param int $id Id of the ugroup (format: projectId_ugroupId)
     */
    public function optionsUsers($id)
    {
        $this->sendAllowHeadersForUserGroupId();
    }

    /**
     * Get the members of a group
     *
     * @throws RestException 404
     *
     * @return PFUser[]
     */
    private function getUserGroupMembers(ProjectUGroup $user_group, $project_id, $limit, $offset)
    {
        return $user_group->getStaticOrDynamicMembersPaginated($project_id, $limit, $offset);
    }

    /**
     * Count the members of a group
     *
     * @return int
     */
    private function countUserGroupMembers(ProjectUGroup $user_group, $project_id)
    {
        return $user_group->countStaticOrDynamicMembers($project_id);
    }

    /**
     * Get the UserRepresentation of a user
     *
     *
     * @return \Tuleap\User\REST\UserRepresentation
     */
    private function getUserRepresentation(PFUser $member)
    {
        $user_representation = new UserRepresentation();
        $user_representation->build($member);

        return $user_representation;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return bool
     */
    private function userCanSeeUserGroups($project_id)
    {
        $project      = $this->project_manager->getProject($project_id);
        $user         = $this->user_manager->getCurrentUser();
        ProjectAuthorization::userCanAccessProject($user, $project, new URLVerification());

        return true;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return bool
     */
    private function userCanSeeUserGroupMembers(ProjectUGroup $ugroup)
    {
        $project = $ugroup->getProject();
        $user    = $this->user_manager->getCurrentUser();

        if ((int) $ugroup->getId() === ProjectUGroup::PROJECT_MEMBERS) {
            ProjectAuthorization::userCanAccessProjectAndCanManageMembership($user, $project);
        } else {
            ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);
        }
    }

    /**
     *
     * @throws RestException 404
     *
     * @return bool
     */
    private function checkGroupIsViewable(int $ugroup_id)
    {
        if (in_array($ugroup_id, ProjectUGroup::SYSTEM_USER_GROUPS, true)) {
            throw new RestException(404, 'Unable to list the users of this group');
        }

        return true;
    }

    private function sendAllowHeadersForUserGroupId()
    {
        Header::allowOptionsGetPut();
    }

    private function sendAllowHeadersForUserGroup()
    {
        Header::allowOptionsPost();
    }

    private function sendPaginationHeaders($limit, $offset, $size)
    {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }

    /**
     * Checks if the limit provided by the request is valid
     *
     * @param int $limit Number of elements displayed per page
     *
     * @return bool
     *
     * @throws RestException 406
     */

    private function checkLimitValueIsAcceptable($limit)
    {
        if ($limit > self::MAX_LIMIT) {
             throw new RestException(406, 'limit value is not acceptable');
        }

        return true;
    }

    /**
     * @return null|UserRepresentation
     */
    private function getUGroupMemberByIdentifier($query, ProjectUGroup $ugroup)
    {
        $user = $this->user_manager->findUser($query);
        if (! $user) {
            throw new RestException(400, 'Unable to find user');
        }

        if ($this->ugroup_member_checker->isUserPartOfUgroupMembers($ugroup->getProject(), $ugroup, $user)) {
            return $this->getUserRepresentation($user);
        }

        return null;
    }

    /**
     * @url OPTIONS
     */
    public function options()
    {
        $this->sendAllowHeadersForUserGroup();
    }

    /**
     * POST user_groups
     *
     * Create an empty user_group
     *
     * @url POST
     *
     * @access protected
     *
     * @param \Tuleap\Project\REST\UserGroupPOSTRepresentation $user_group_representation Ugroup representation {@from body}
     *
     * @return UserGroupRepresentation {@type \Tuleap\Project\REST\v1\UserGroupRepresentation}
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     *
     * @status 201
     */
    protected function postUgroups(UserGroupPOSTRepresentation $user_group_representation)
    {
        try {
            $this->checkAccess();

            $project_id   = $user_group_representation->project_id;
            $project      = $this->project_manager->getProject($project_id);
            $user         = $this->user_manager->getCurrentUser();

            ProjectStatusVerificator::build()->checkProjectStatusAllowsAllUsersToAccessIt(
                $project
            );

            ProjectAuthorization::userCanAccessProjectAndIsProjectAdmin($user, $project);

            $new_ugroup_id = $this->ugroup_manager->createEmptyUgroup(
                $project_id,
                $user_group_representation->short_name,
                $user_group_representation->description
            );

            $new_ugroup                = $this->ugroup_manager->getById($new_ugroup_id);
            $new_ugroup_representation = new UserGroupRepresentation();
            $new_ugroup_representation->build((int) $project_id, $new_ugroup);

            return $new_ugroup_representation;
        } catch (CannotCreateUGroupException $exception) {
            throw new RestException(400, $exception->getMessage());
        } finally {
            $this->sendAllowHeadersForUserGroup();
        }
    }
}
