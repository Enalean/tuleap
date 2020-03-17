<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Luracast\Restler\RestException;
use PaginatedUserCollection;
use PFUser;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\JsonDecoder;
use Tuleap\User\AccessKey\AccessKeyDAO;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;
use Tuleap\User\AccessKey\REST\UserAccessKeyRepresentation;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeBuilderCollector;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeDAO;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;
use Tuleap\User\AccessKey\Scope\CoreAccessKeyScopeBuilderFactory;
use Tuleap\User\Admin\UserStatusChecker;
use Tuleap\User\History\HistoryCleaner;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryRetriever;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\REST\UserRepresentation;
use UGroupLiteralizer;
use User_ForgeUserGroupPermission_RetrieveUserMembershipInformation;
use User_ForgeUserGroupPermission_UserManagement;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

/**
 * Wrapper for users related REST methods
 */
class UserResource extends AuthenticatedResource
{

    public const SELF_ID         = 'self';
    public const MAX_LIMIT       = 50;
    public const DEFAULT_LIMIT   = 10;
    public const DEFAULT_OFFSET  = 0;
    public const MAX_TIMES_BATCH = 100;

    /** @var JsonDecoder */
    private $json_decoder;

    /** @var UGroupLiteralizer */
    private $ugroup_literalizer;

    /** @var UserManager */
    private $user_manager;

    /**
     * @var HistoryRetriever
     */
    private $history_retriever;

    /** @var User_ForgeUserGroupPermissionsManager */
    private $forge_ugroup_permissions_manager;

    public function __construct()
    {
        $this->user_manager       = UserManager::instance();
        $this->json_decoder       = new JsonDecoder();
        $this->ugroup_literalizer = new UGroupLiteralizer();
        $this->history_retriever  = new HistoryRetriever(\EventManager::instance());

        $this->forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * Get a user
     *
     * Get the definition of a given user.
     * <pre> Note that when accessing this route without authentication certain properties<br>
     * will not be returned in the response.
     * </pre>
     * <br>
     * The user ID can be either:
     * <ul>
     *   <li>an integer value to get this specific user information</li>
     *   <li>the "self" value to get our own user information</li>
     * </ul>
     *
     * @url GET {id}
     * @access hybrid
     *
     * @param string $id Id of the desired user
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return UserRepresentation {@type UserRepresentation}
     */
    public function getId(string $id)
    {
        $this->checkAccess();

        $user_id = null;
        if ($id === self::SELF_ID) {
            $user_id = (int) $this->user_manager->getCurrentUser()->getId();
        } elseif (ctype_digit($id)) {
            $user_id = (int) $id;
        }

        if ($user_id === null) {
            throw new RestException(400, 'Provided User Id is not well formed.');
        }

        $user                = $this->getUserById($user_id);
        $user_representation = ($this->is_authenticated) ? new UserRepresentation() : new MinimalUserRepresentation();
        return $user_representation->build($user);
    }

    /**
     * @url OPTIONS {id}
     *
     * @param int $id Id of the user
     *
     * @access public
     *
     * @throws RestException 400
     * @throws RestException 404
     */
    public function optionsId($id)
    {
        $this->sendAllowHeaders();
    }

    /**
     * @url OPTIONS
     *
     * @access public
     */
    public function options()
    {
        $this->sendAllowHeaders();
    }

    /**
     * Get users
     *
     * Get all users matching the query.
     * <pre> Note that when accessing this route without authentication certain properties<br>
     * will not be returned in the response.
     * </pre>
     *
     * <br>
     * ?query can be either:
     * <ul>
     *   <li>a simple string, then it will search on "real_name" and "username" with wildcard</li>
     *   <li>a json object to search on username with exact match: {"username": "john_doe"}</li>
     * </ul>
     *
     * @access hybrid
     *
     * @param string $query  Search string (3 chars min in length) {@from query} {@min 3}
     * @param int    $limit  Number of elements displayed per page
     * @param int    $offset Position of the first element to display
     *
     * @return array {@type UserRepresentation}
     */
    public function get(
        $query,
        $limit = self::DEFAULT_LIMIT,
        $offset = self::DEFAULT_OFFSET
    ) {
        $this->checkAccess();
        if ($this->json_decoder->looksLikeJson($query)) {
            $user_collection = $this->getUserFromExactSearch($query);
        } else {
            $user_collection = $this->getUsersFromPatternSearch($query, $offset, $limit);
        }

        return $this->getUsersListRepresentation($user_collection, $offset, $limit);
    }

    private function getUserFromExactSearch($query)
    {
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);
        if (! isset($json_query['username'])) {
            throw new RestException(400, 'You can only search on "username"');
        }
        $user  = $this->user_manager->getUserByUserName($json_query['username']);
        $users = array();
        if ($user !== null) {
            $users[] = $user;
        }
        return new PaginatedUserCollection(
            $users,
            count($users)
        );
    }

    private function getUsersFromPatternSearch($query, $offset, $limit)
    {
        $exact = false;
        return $this->user_manager->getPaginatedUsersByUsernameOrRealname(
            $query,
            $exact,
            $offset,
            $limit
        );
    }

    private function getUsersListRepresentation(PaginatedUserCollection $user_collection, $offset, $limit)
    {
        $this->sendAllowHeaders();
        Header::sendPaginationHeaders(
            $limit,
            $offset,
            $user_collection->getTotalCount(),
            self::MAX_LIMIT
        );

        $list_of_user_representation = array();
        foreach ($user_collection->getUsers() as $user) {
            $user_representation = ($this->is_authenticated) ? new UserRepresentation() : new MinimalUserRepresentation();
            $list_of_user_representation[] = $user_representation->build($user);
        }

        return $list_of_user_representation;
    }

    /**
     * Get the list of user groups the given user is member of
     *
     * This list of groups is displayed as an array of string:
     * <pre>
     * [
     *     "site_active",
     *     "%project-name%_project_members",
     *     "%project-name%_project_admin",
     *     "ug_101"
     *     ...
     * ]
     * </pre>
     *
     * @url GET {id}/membership
     * @access protected
     *
     * @param int $id Id of the desired user
     *
     * @throws RestException 400
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return array {@type string}
     */
    public function getMembership($id)
    {
        $this->checkAccess();

        $watchee = $this->getUserById($id);
        $watcher = $this->user_manager->getCurrentUser();
        if ($this->checkUserCanSeeOtherUser($watcher, $watchee)) {
            return $this->ugroup_literalizer->getUserGroupsForUser($watchee);
        }
        throw new RestException(403, "Cannot see other's membreship");
    }

    /**
     * @url OPTIONS {id}/preferences
     *
     * @param int $id Id of the user
     *
     * @access public
     */
    public function optionPreferences($id)
    {
        Header::allowOptionsGetPatchDelete();
    }

    /**
     * Get a user preference
     *
     * @url GET {id}/preferences
     *
     * @access hybrid
     *
     * @param int    $id  Id of the desired user
     * @param string $key Preference key
     *
     * @throws RestException 401
     * @throws RestException 403
     * @throws RestException 404
     *
     * @return UserPreferenceRepresentation
     */
    public function getPreferences($id, $key)
    {
        $this->checkAccess();
        $this->optionPreferences($id);

        if ($id != $this->user_manager->getCurrentUser()->getId()) {
            throw new RestException(403, 'You can only access to your own preferences');
        }

        $value = $this->getUserPreference($id, $key);

        $preference_representation = new UserPreferenceRepresentation();
        $preference_representation->build($key, $value);

        return $preference_representation;
    }

    /**
     * Delete a user preference
     *
     * @url DELETE {id}/preferences
     *
     * @access hybrid
     *
     * @param int    $id Id of the desired user
     * @param string $key Preference key
     *
     * @throws RestException 401
     * @throws RestException 500
     */
    public function deletePreferences($id, $key)
    {
        $this->checkAccess();
        $this->optionPreferences($id);

        if ($id != $this->user_manager->getCurrentUser()->getId()) {
            throw new RestException(403, 'You can only set your own preferences');
        }

        if (! $this->deleteUserPreference($id, $key)) {
            throw new RestException(500, 'Unable to delete the user preference');
        }
    }

    /**
     * Set a user preference
     *
     * @url PATCH {id}/preferences
     *
     * @access hybrid
     *
     * @param int $id Id of the desired user
     * @param UserPreferenceRepresentation $preference Preference representation {@from body}
     *
     * @throws RestException 401
     * @throws RestException 500
     *
     * @return UserPreferenceRepresentation
     */
    public function patchPreferences($id, $preference)
    {
        $this->checkAccess();
        $this->optionPreferences($id);

        if ($id != $this->user_manager->getCurrentUser()->getId()) {
            throw new RestException(403, 'You can only set your own preferences');
        }

        if ($this->user_manager->getCurrentUser()->isAnonymous()) {
            throw new RestException(404, 'User not found');
        }

        if (! $this->setUserPreference($id, $preference->key, $preference->value)) {
            throw new RestException(500, 'Unable to set the user preference');
        }
    }

    private function getUserPreference($user_id, $key)
    {
        return $this->user_manager->getUserById($user_id)->getPreference($key);
    }

    private function setUserPreference($user_id, $key, $value)
    {
        return $this->user_manager->getUserById($user_id)->setPreference($key, $value);
    }

    private function deleteUserPreference($user_id, $key)
    {
        return $this->user_manager->getUserById($user_id)->delPreference($key);
    }

    private function checkUserCanSeeOtherUser(PFUser $watcher, PFUser $watchee)
    {
        if ($watcher->isSuperUser()) {
            return true;
        }
        if ($watcher->getId() === $watchee->getId()) {
            return true;
        }

        return (
            $this->forge_ugroup_permissions_manager->doesUserHavePermission(
                $watcher,
                new User_ForgeUserGroupPermission_RetrieveUserMembershipInformation()
            )
            || $this->forge_ugroup_permissions_manager->doesUserHavePermission(
                $watcher,
                new User_ForgeUserGroupPermission_UserManagement()
            )
        );
    }


    /**
     * Partial update of user details
     *
     * Things to take into account:
     * <ol>
     *  <li>You don't need to set all 'values' of the user, you can restrict to the modified ones</li>
     *  <li>Possible fields are:"email", "real_name", "username" and "status"
     *  <li>Examples: To update a user status and username, the values must be an array:
     * <pre>
     * {
     * "status" : "S"
     * ,
     *
     * "username": "johnd"
     * }
     * </pre>
     * </li>
     * </ol>
     *
     * @url PATCH {id}
     * @param string  $id        Id of the user
     * @param Array   $values    User fields values
     *
     * @throws RestException
     */
    protected function patchUserDetails($id, array $values)
    {
        $user_to_update = $this->getUserById($id);
        $current_user = $this->user_manager->getCurrentUser();
        if ($this->checkUserCanUpdate($current_user)) {
            foreach ($values as $key => $value) {
                switch ($key) {
                    case "status":
                        $this->updateUserStatus($user_to_update, $value);
                        break;
                    case "email":
                        $user_to_update->setEmail($value);
                        break;
                    case "real_name":
                        $user_to_update->setRealName($value);
                        break;
                    case "username":
                        $user_to_update->setUserName($value);
                        break;
                    default:
                        break;
                }
            }
            return $this->user_manager->updateDb($user_to_update);
        }
        throw new RestException(403, "Cannot update other's details");
    }

    /**
     * Check if user has permission to update user details
     *
     * @return bool
     *
     */
    private function checkUserCanUpdate(PFUser $current_user)
    {
        if ($current_user->isSuperUser()) {
            return true;
        }

        return $this->forge_ugroup_permissions_manager->doesUserHavePermission(
            $current_user,
            new User_ForgeUserGroupPermission_UserManagement()
        );
    }

    /**
     * @throws RestException
     */
    private function updateUserStatus(PFUser $user_to_update, string $value): void
    {
        if ($value === PFUser::STATUS_RESTRICTED) {
            $user_status_checker = new UserStatusChecker();
            if (! $user_status_checker->doesPlatformAllowRestricted()) {
                throw new RestException(400, "Restricted users are not authorized.");
            }
            if (! $user_status_checker->isRestrictedStatusAllowedForUser($user_to_update)) {
                throw new RestException(400, "This user can't be restricted.");
            }
        }

        $user_to_update->setStatus($value);
    }

    private function getUserById($id)
    {
        $user = $this->user_manager->getUserById($id);

        if (! $user) {
            throw new RestException(404, 'User Id not found');
        }

        return $user;
    }

    private function sendAllowHeaders()
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * @url OPTIONS {id}/history
     *
     * @param int $id Id of the user
     *
     * @access public
     */
    public function optionHistory($id)
    {
        $this->sendAllowHeadersForHistory();
    }

    private function sendAllowHeadersForHistory()
    {
        Header::allowOptionsGetPut();
    }

    /**
     * Get the history of a user
     *
     * @url GET {id}/history
     *
     * @access hybrid
     *
     * @param int    $id  Id of the desired user
     *
     * @throws RestException 403
     *
     * @return UserHistoryRepresentation {@type UserHistoryRepresentation}
     */
    public function getHistory($id)
    {
        $this->sendAllowHeadersForHistory();

        $this->checkAccess();

        $current_user = $this->user_manager->getCurrentUser();
        $this->checkUserCanAccessToTheHistory($current_user, $id);

        $history_representation = new UserHistoryRepresentation();
        $history                = $this->history_retriever->getHistory($current_user);

        $filtered_history = array_filter(
            $history,
            function (HistoryEntry $entry) use ($current_user) {
                return $current_user->isSuperUser() || ! $entry->getProject()->isSuspended();
            }
        );

        $history_representation->build($filtered_history);

        return $history_representation;
    }

    /**
     * Clear the history of a user
     *
     * Arbitrary manipulations of the history other than clear are not accepted.
     *
     * @url PUT {id}/history
     *
     * @access hybrid
     *
     * @param int    $id  Id of the desired user
     * @param UserHistoryEntryRepresentation[] $history_entries History entries representation {@from body}
     *
     * @throws RestException 403
     */
    public function putHistory($id, array $history_entries)
    {
        $this->sendAllowHeadersForHistory();

        $this->checkAccess();

        $current_user = $this->user_manager->getCurrentUser();
        $this->checkUserCanAccessToTheHistory($current_user, $id);

        if (! empty($history_entries)) {
            throw new RestException(403, 'You can only clear your history');
        }

        $history_cleaner = new HistoryCleaner(\EventManager::instance());
        $history_cleaner->clearHistory($current_user);
    }

    /**
     * @throws RestException
     */
    private function checkUserCanAccessToTheHistory(\PFUser $current_user, $requested_user_id)
    {
        if ($requested_user_id != $current_user->getId()) {
            throw new RestException(403, 'You can only access to your own history');
        }
    }

    /**
     * @url OPTIONS {id}/access_keys
     *
     * @access protected
     */
    public function optionAccessKey($id)
    {
        Header::allowOptionsGet();
    }

    /**
     * Get the access keys of a user
     *
     * @url GET {id}/access_keys
     *
     * @param int $id Id of the user
     * @param int $limit  Number of elements displayed {@from path}{@min 1}{@max 50}
     * @param int $offset Position of the first element to display {@from path}{@min 0}
     *
     * @access protected
     *
     * @throws RestException 403
     *
     * @return array {@type \Tuleap\User\AccessKey\REST\UserAccessKeyRepresentation}
     */
    public function getAccessKeys($id, $limit = self::MAX_LIMIT, $offset = 0)
    {
        $this->optionAccessKey($id);
        $this->checkAccess();

        $current_user = $this->user_manager->getCurrentUser();

        if ($id != $current_user->getId()) {
            throw new RestException(403, 'You can only access to your own access keys');
        }

        $access_key_metadata_retriever = new AccessKeyMetadataRetriever(
            new AccessKeyDAO(),
            new AccessKeyScopeRetriever(
                new AccessKeyScopeDAO(),
                AggregateAuthenticationScopeBuilder::fromBuildersList(
                    CoreAccessKeyScopeBuilderFactory::buildCoreAccessKeyScopeBuilder(),
                    AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new AccessKeyScopeBuilderCollector())
                )
            )
        );
        $all_access_key_medatada       = $access_key_metadata_retriever->getMetadataByUser($current_user);

        Header::sendPaginationHeaders($limit, $offset, count($all_access_key_medatada), self::MAX_LIMIT);

        $access_key_representations = [];
        foreach (array_slice($all_access_key_medatada, $offset, $limit) as $access_key_metadata) {
            $representation = new UserAccessKeyRepresentation();
            $representation->build($access_key_metadata);
            $access_key_representations[] = $representation;
        }

        return $access_key_representations;
    }
}
