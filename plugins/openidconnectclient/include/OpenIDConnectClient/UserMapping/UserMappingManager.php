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
     * @throws UserMappingNotFoundException
     */
    public function getByProviderAndIdentifier(Provider $provider, $identifier) {
        $row = $this->dao->searchByIdentifierAndProviderId($identifier, $provider->getId());
        if ($row === false) {
            throw new UserMappingNotFoundException();
        }
        return $this->instantiateFromRow($row);
    }

    /**
     * @return UserMapping
     */
    private function instantiateFromRow(array $row) {
        return new UserMapping(
            $row['user_id'],
            $row['provider_id'],
            $row['user_openidconnect_identifier']
        );
    }

}