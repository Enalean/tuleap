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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

/**
 * Represent account that have been successfully authenticated by an
 * OpenID Connect provider but no yet linked to a Tuleap account.
 *
 * The whole linking process is the following:
 * Ask to be authenticated by OpenID Connect ->
 * Accept that Tuleap use informations from the OpenID Connect provider ->
 * Search if a user has already linked his account with the given user identifier and provider ->
 * If no, check if the user has control of a Tuleap account ->
 * Create a link between the account and the OpenID Connect provider for this user
 */
class UnlinkedAccount
{

    private $id;
    private $provider_id;
    private $user_identifier;

    public function __construct($id, $provider_id, $user_identifier)
    {
        $this->id              = $id;
        $this->provider_id     = $provider_id;
        $this->user_identifier = $user_identifier;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProviderId()
    {
        return $this->provider_id;
    }

    public function getUserIdentifier()
    {
        return $this->user_identifier;
    }
}
