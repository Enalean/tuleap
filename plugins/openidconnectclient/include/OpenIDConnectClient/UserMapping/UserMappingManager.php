<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\UserMapping;

use PFUser;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use UserDao;

class UserMappingManager
{
    /**
     * @var UserMappingDao
     */
    private $dao;
    /**
     * @var UserDao
     */
    private $user_dao;
    /**
     * @var CanRemoveUserMappingChecker
     */
    private $can_remove_user_mapping_checker;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        UserMappingDao $dao,
        UserDao $user_dao,
        CanRemoveUserMappingChecker $can_remove_user_mapping_checker,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->dao                             = $dao;
        $this->user_dao                        = $user_dao;
        $this->can_remove_user_mapping_checker = $can_remove_user_mapping_checker;
        $this->transaction_executor            = $transaction_executor;
    }

    /**
     * @throws UserMappingDataAccessException
     */
    public function create(int $user_id, $provider_id, $identifier, $last_used): void
    {
        if ($user_id < \UserManager::SPECIAL_USERS_LIMIT) {
            throw new CannotCreateAMappingForASpecialUserException($user_id);
        }
        $this->user_dao->storeLoginSuccess($user_id, $last_used);
        $is_saved  = $this->dao->save($user_id, $provider_id, $identifier, $last_used);
        if (! $is_saved) {
            throw new UserMappingDataAccessException();
        }
    }

    /**
     * @return UserMapping
     * @throws UserMappingNotFoundException
     */
    public function getById($id)
    {
        $rows = $this->dao->searchById($id);
        if ($rows === false) {
            throw new UserMappingNotFoundException();
        }
        $row = $rows->getRow();
        if ($row === false) {
            throw new UserMappingNotFoundException();
        }
        return $this->instantiateUserMappingFromRow($row);
    }

    /**
     * @return UserMapping
     * @throws UserMappingNotFoundException
     */
    public function getByProviderAndIdentifier(Provider $provider, $identifier)
    {
        $row = $this->dao->searchByIdentifierAndProviderId($identifier, $provider->getId());
        if ($row === false) {
            throw new UserMappingNotFoundException();
        }
        return $this->instantiateUserMappingFromRow($row);
    }

    /**
     * @return UserMapping
     * @throws UserMappingNotFoundException
     */
    public function getByProviderAndUser(Provider $provider, PFUser $user)
    {
        $row = $this->dao->searchByProviderIdAndUserId($provider->getId(), $user->getId());
        if ($row === false) {
            throw new UserMappingNotFoundException();
        }
        return $this->instantiateUserMappingFromRow($row);
    }

    /**
     * @return UserMappingUsage[]
     */
    public function getUsageByUser(PFUser $user)
    {
        $user_mappings_usage = [];
        $rows                = $this->dao->searchUsageByUserId($user->getId());

        if ($rows === false) {
            return $user_mappings_usage;
        }

        foreach ($rows as $row) {
            $user_mappings_usage[] = $this->instantiateUserMappingUsageFromRow($row);
        }

        return $user_mappings_usage;
    }

    public function userHasProvider(PFUser $user): bool
    {
        $results = $this->dao->searchUsageByUserId($user->getId());
        if ($results === false) {
            return false;
        }
        return count($results) > 0;
    }

    /**
     * @throws UserMappingDataAccessException
     */
    public function remove(PFUser $user, UserMapping $user_mapping): void
    {
        if ((int) $user->getId() !== $user_mapping->getUserId()) {
            throw new \InvalidArgumentException('The provided user is not part of this user mapping');
        }

        $this->transaction_executor->execute(
            function () use ($user, $user_mapping): void {
                if (! $this->can_remove_user_mapping_checker->canAUserMappingBeRemoved($user, $this->getUsageByUser($user))) {
                    throw new UserMappingDataAccessException();
                }

                $is_deleted = $this->dao->deleteById($user_mapping->getId());
                if (! $is_deleted) {
                    throw new UserMappingDataAccessException();
                }
            }
        );
    }

    /**
     * @throws UserMappingDataAccessException
     */
    public function updateLastUsed(UserMapping $user_mapping, int $last_used): void
    {
        $this->user_dao->storeLoginSuccess($user_mapping->getUserId(), $last_used);
        $is_updated = $this->dao->updateLastUsed(
            $user_mapping->getId(),
            $last_used
        );
        if (! $is_updated) {
            throw new UserMappingDataAccessException();
        }
    }

    /**
     * @return UserMapping
     */
    private function instantiateUserMappingFromRow(array $row)
    {
        return new UserMapping(
            $row['id'],
            $row['user_id'],
            $row['provider_id'],
            $row['user_openidconnect_identifier'],
            $row['last_used']
        );
    }

    /**
     * @return UserMappingUsage
     */
    private function instantiateUserMappingUsageFromRow(array $row)
    {
        return new UserMappingUsage(
            $row['user_mapping_id'],
            $row['provider_id'],
            $row['name'],
            $row['icon'],
            $row['unique_authentication_endpoint'],
            $row['user_id'],
            $row['last_used']
        );
    }
}
