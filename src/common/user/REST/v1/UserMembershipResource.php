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
     * Get membership info for a list of users
     *
     * @url GET
     * @access protected
     * @throws 406
     *
     * @param string $users List of username {@from query} {@type string}
     * @param int $limit  Number of elements displayed per page
     * @param int $offset Position of the first element to display
     *
     * @return array {@type Tuleap\User\REST\v1\UserMembershipRepresentation}
     */
    public function get($users, $limit, $offset) {
        if ($limit > self::MAX_LIMIT) {
            throw new RestException(406, 'Maximum value for limit exceeded');
        }

        $usernames    = explode(",",$users);
        $current_user = $this->user_manager->getCurrentUser();

        $users_memberships = $this->getUsersMembershipsFromUsernames(
            $current_user,
            array_slice($usernames, $offset, $limit)
        );

        Header::sendPaginationHeaders($limit, $offset, count($usernames), self::MAX_LIMIT);
        $this->sendAllowHeaders();

        return $users_memberships;
    }

    private function getUsersMembershipsFromUsernames(PFUser $current_user, $usernames) {
        $result = array();

        foreach ($usernames as $username) {
            $user = $this->user_manager->getUserByUsername($username);

            if (! $user) {
                continue;
            }

            if (! $this->checkUserCanSeeOtherUser($current_user, $user)) {
                continue;
            }

            $representation = new UserMembershipRepresentation();
            $representation->build($username, $this->ugroup_literalizer->getUserGroupsForUser($user));

            $result[] = $representation;
        }

        return $result;
    }

    private function checkUserCanSeeOtherUser(PFUser $watcher, PFuser $watchee) {
        if ($watcher->isSuperUser()) {
            return true;
        }
        if ($watcher->getId() === $watchee->getId()) {
            return true;
        }

        return ($this->forge_ugroup_permissions_manager->doesUserHavePermission(
                $watcher, new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation()
            ) || $this->forge_ugroup_permissions_manager->doesUserHavePermission(
                $watcher, new User_ForgeUserGroupPermission_UserManagement()
            ));
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
