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

/**
 * @psalm-immutable
 */
class UserMappingUsage
{

    private $user_mapping_id;
    private $provider_icon;
    private $provider_id;
    private $provider_name;
    private $is_unique_authentication_endpoint;
    private $user_id;
    private $last_used;

    public function __construct(
        $user_mapping_id,
        $provider_id,
        $provider_name,
        $provider_icon,
        $is_unique_authentication_endpoint,
        $user_id,
        int $last_used
    ) {
        $this->user_mapping_id                   = $user_mapping_id;
        $this->provider_id                       = $provider_id;
        $this->provider_name                     = $provider_name;
        $this->user_id                           = $user_id;
        $this->last_used                         = $last_used;
        $this->provider_icon                     = $provider_icon;
        $this->is_unique_authentication_endpoint = $is_unique_authentication_endpoint;
    }

    public function getUserMappingId()
    {
        return $this->user_mapping_id;
    }

    public function getProviderId()
    {
        return $this->provider_id;
    }

    public function getProviderName()
    {
        return $this->provider_name;
    }

    public function getProviderIcon()
    {
        return $this->provider_icon;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getLastUsage(): int
    {
        return $this->last_used;
    }

    public function isUsedAsUniqueAuthenticationEndpoint()
    {
        return $this->is_unique_authentication_endpoint;
    }
}
