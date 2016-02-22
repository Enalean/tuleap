<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DateTime;
use PFUser;
use Tuleap\OpenIDConnectClient\Provider\Provider;

class UserMappingManager {

    /**
     * @var UserMappingDao
     */
    private $dao;

    public function __construct(UserMappingDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @return UserMapping
     * @throws UserMappingDataAccessException
     */
    public function create($user_id, $provider_id, $identifier, $last_used) {
        $is_saved  = $this->dao->save($user_id, $provider_id, $identifier, $last_used);
        if (! $is_saved) {
            throw new UserMappingDataAccessException();
        }
        return new UserMapping($user_id, $provider_id, $identifier, $last_used);
    }

    /**
     * @return UserMapping
     * @throws UserMappingNotFoundException
     */
    public function getByProviderAndIdentifier(Provider $provider, $identifier) {
        $row = $this->dao->searchByIdentifierAndProviderId($identifier, $provider->getId());
        if ($row === false) {
            throw new UserMappingNotFoundException();
        }
        return $this->instantiateUserMappingFromRow($row);
    }

    /**
     * @return UserMappingUsage[]
     */
    public function getUsageByUser(PFUser $user) {
        $user_mappings_usage = array();
        $rows                = $this->dao->searchUsageByUserId($user->getId());

        if ($rows === false) {
            return $user_mappings_usage;
        }

        foreach($rows as $row) {
            $user_mappings_usage[] = $this->instantiateUserMappingUsageFromRow($row);
        }

        return $user_mappings_usage;
    }

    /**
     * @throws UserMappingDataAccessException
     */
    public function removeByUserAndProvider(PFUser $user, Provider $provider) {
        $is_deleted = $this->dao->deleteByUserIdAndProviderId($user->getId(), $provider->getId());
        if (! $is_deleted) {
            throw new UserMappingDataAccessException();
        }
    }

    /**
     * @throws UserMappingDataAccessException
     */
    public function updateLastUsed(UserMapping $user_mapping, $last_used) {
        $is_updated = $this->dao->updateLastUsed(
            $user_mapping->getUserId(),
            $user_mapping->getProviderId(),
            $last_used
        );
        if (! $is_updated) {
            throw new UserMappingDataAccessException();
        }
    }

    /**
     * @return UserMapping
     */
    private function instantiateUserMappingFromRow(array $row) {
        return new UserMapping(
            $row['user_id'],
            $row['provider_id'],
            $row['user_openidconnect_identifier'],
            $row['last_used']
        );
    }

    /**
     * @return UserMappingUsage
     */
    private function instantiateUserMappingUsageFromRow(array $row) {
        return new UserMappingUsage(
            $row['provider_id'],
            $row['name'],
            $row['user_id'],
            $row['last_used']
        );
    }

}