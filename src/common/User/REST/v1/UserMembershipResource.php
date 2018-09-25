<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

namespace Tuleap\User\REST\v1;

use PFUser;
use UserManager;
use UGroupLiteralizer;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\UserManager as RestUserManager;
use Luracast\Restler\RestException;
use User_ForgeUserGroupPermission_RetrieveUserMembershipInformation;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsManager;
use User_ForgeUserGroupPermissionsDao;
use Tuleap\REST\AuthenticatedResource;

/**
 * Get Memberships For a list of Users
 */
class UserMembershipResource extends AuthenticatedResource {
    const MAX_LIMIT = 1000;

    const CRITERION_WITH_SSH_KEY = 'with_ssh_key';

    /** @var UserManager */
    private $user_manager;

    /** @var JsonDecoder */
    private $json_decoder;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var \Tuleap\REST\UserManager */
    private $rest_user_manager;

    /** @var User_ForgeUserGroupPermissionsManager */
    private $forge_ugroup_permissions_manager;

    public function __construct() {
        $this->user_manager       = UserManager::instance();
        $this->json_decoder       = new JsonDecoder();
        $this->ugroup_literalizer = new UGroupLiteralizer();
        $this->rest_user_manager  = RestUserManager::build();

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * Retrieve membership information for a set of users
     *
     * This resource will return user group membership information,
     * i.e. all the groups to which each one belongs,
     * for all users meeting the "query" criterion
     *
     * @url GET
     * @access protected
     * @throws 406
     *
     * @param string $query Criterion to filter the results {@choice with_ssh_key}
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\User\REST\v1\UserMembershipRepresentation}
     */
    public function get($query, $offset = 0, $limit = 10) {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        if ($query !== self::CRITERION_WITH_SSH_KEY) {
            throw new RestException(406, 'Invalid query criteria');
        }

        $current_user = $this->user_manager->getCurrentUser();
        $this->checkUserCanSeeOtherUsers($current_user);

        $users_memberships = array();
        $paginated_users = $this->user_manager->getPaginatedUsersWithSshKey($offset, $limit);
        foreach ($paginated_users->getUsers() as $user) {
            $representation = new UserMembershipRepresentation();
            $representation->build($user->getUsername(), $this->ugroup_literalizer->getUserGroupsForUser($user));

            $users_memberships[] = $representation;
        }

        Header::sendPaginationHeaders($limit, $offset, $paginated_users->getTotalCount(), self::MAX_LIMIT);
        $this->sendAllowHeaders();

        return $users_memberships;
    }

    private function checkUserCanSeeOtherUsers(PFUser $user) {
        if ($user->isSuperUser()) {
            return;
        }

        if ($this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $user,
            new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation()
        )) {
            return;
        }

        if ($this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $user,
            new User_ForgeUserGroupPermission_UserManagement()
        )) {
            return;
        }

        throw new RestException(403);
    }

    /**
     * @url OPTIONS
     *
     * @access public
     *
     * @throws 400
     * @throws 404
     */
    public function options() {
        $this->sendAllowHeaders();
    }

    private function sendAllowHeaders() {
        Header::allowOptionsGet();
    }
}
