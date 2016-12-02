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

namespace Tuleap\OpenIDConnectClient\Provider;

use PFUser;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingNotFoundException;

class EnableUniqueAuthenticationEndpointVerifier
{
    /**
     * @var UserMappingManager
     */
    private $user_mapping_manager;

    public function __construct(UserMappingManager $user_mapping_manager)
    {
        $this->user_mapping_manager = $user_mapping_manager;
    }

    /**
     * @return bool
     */
    public function canBeEnabledBy(Provider $provider, PFUser $user)
    {
        if (! $user->isSuperUser()) {
            return false;
        }

        if ($provider->isUniqueAuthenticationEndpoint()) {
            return true;
        }

        try {
            $this->user_mapping_manager->getByProviderAndUser($provider, $user);
        } catch (UserMappingNotFoundException $ex) {
            return false;
        }

        return true;
    }
}
