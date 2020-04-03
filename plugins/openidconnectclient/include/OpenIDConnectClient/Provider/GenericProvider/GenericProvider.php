<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\Provider\GenericProvider;

use ForgeConfig;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class GenericProvider implements Provider
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $authorization_endpoint;

    /**
     * @var string
     */
    private $token_endpoint;

    /**
     * @var string
     */
    private $user_info_endpoint;

    /**
     * @var string
     */
    private $client_id;

    /**
     * @var string
     */
    private $client_secret;

    /**
     * @var bool
     */
    private $is_unique_authentication_endpoint;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $color;

    public function __construct(
        int $id,
        string $name,
        string $authorization_endpoint,
        string $token_endpoint,
        string $user_info_endpoint,
        string $client_id,
        string $client_secret,
        bool $is_unique_authentication_endpoint,
        string $icon,
        string $color
    ) {
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->authorization_endpoint;
    }

    public function getTokenEndpoint(): string
    {
        return $this->token_endpoint;
    }

    public function getUserInfoEndpoint(): string
    {
        return $this->user_info_endpoint;
    }

    public function getClientId(): string
    {
        return $this->client_id;
    }

    public function getClientSecret(): string
    {
        return $this->client_secret;
    }

    public function isUniqueAuthenticationEndpoint(): bool
    {
        return $this->is_unique_authentication_endpoint;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getRedirectUri(): string
    {
        return 'https://' . ForgeConfig::get('sys_https_host') . '/plugins/openidconnectclient/';
    }
}
