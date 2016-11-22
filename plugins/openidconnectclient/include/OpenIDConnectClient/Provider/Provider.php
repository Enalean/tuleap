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


class Provider {

    private $id;
    private $name;
    private $authorization_endpoint;
    private $token_endpoint;
    private $user_info_endpoint;
    private $client_id;
    private $client_secret;
    private $is_unique_authentication_endpoint;
    private $icon;
    private $color;


    public function __construct(
        $id,
        $name,
        $authorization_endpoint,
        $token_endpoint,
        $user_info_endpoint,
        $client_id,
        $client_secret,
        $is_unique_authentication_endpoint,
        $icon,
        $color
    )
    {
        $this->id                                = $id;
        $this->name                              = $name;
        $this->authorization_endpoint            = $authorization_endpoint;
        $this->token_endpoint                    = $token_endpoint;
        $this->user_info_endpoint                = $user_info_endpoint;
        $this->client_id                         = $client_id;
        $this->client_secret                     = $client_secret;
        $this->is_unique_authentication_endpoint = $is_unique_authentication_endpoint;
        $this->icon                              = $icon;
        $this->color                             = $color;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getAuthorizationEndpoint() {
        return $this->authorization_endpoint;
    }

    public function getTokenEndpoint() {
        return $this->token_endpoint;
    }

    public function getUserInfoEndpoint() {
        return $this->user_info_endpoint;
    }

    public function getClientId() {
        return $this->client_id;
    }

    public function getClientSecret() {
        return $this->client_secret;
    }

    public function isUniqueAuthenticationEndpoint()
    {
        return $this->is_unique_authentication_endpoint;
    }

    public function getIcon() {
        return $this->icon;
    }

    public function getColor() {
        return $this->color;
    }

}
