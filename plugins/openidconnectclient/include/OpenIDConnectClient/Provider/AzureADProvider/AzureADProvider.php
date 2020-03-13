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

namespace Tuleap\OpenIDConnectClient\Provider\AzureADProvider;

use ForgeConfig;
use Tuleap\OpenIDConnectClient\Provider\Provider;

final class AzureADProvider implements Provider
{
    private const BASE_AZURE_URL = "https://login.microsoftonline.com/";

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

    /**
     * @var string
     */
    private $tenant_id;
    /**
     * @var AcceptableTenantForAuthenticationConfiguration
     */
    private $acceptable_tenant_for_authentication_configuration;

    public function __construct(
        int $id,
        string $name,
        string $client_id,
        string $client_secret,
        bool $is_unique_authentication_endpoint,
        string $icon,
        string $color,
        string $tenant_id,
        AcceptableTenantForAuthenticationConfiguration $acceptable_tenant_for_authentication_configuration
    ) {
        $this->id                                                 = $id;
        $this->name                                               = $name;
        $this->client_id                                          = $client_id;
        $this->client_secret                                      = $client_secret;
        $this->is_unique_authentication_endpoint                  = $is_unique_authentication_endpoint;
        $this->icon                                               = $icon;
        $this->color                                              = $color;
        $this->tenant_id                                          = $tenant_id;
        $this->acceptable_tenant_for_authentication_configuration = $acceptable_tenant_for_authentication_configuration;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthorizationEndpoint() : string
    {
        return self::BASE_AZURE_URL . urlencode($this->acceptable_tenant_for_authentication_configuration->getValueForAuthenticationFlow()) . "/oauth2/v2.0/authorize";
    }

    public function getTokenEndpoint() : string
    {
        return self::BASE_AZURE_URL . urlencode($this->acceptable_tenant_for_authentication_configuration->getValueForAuthenticationFlow()) . "/oauth2/v2.0/token";
    }

    public function getUserInfoEndpoint() : string
    {
        return "https://graph.microsoft.com/oidc/userinfo";
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

    public function getTenantId() : string
    {
        return $this->tenant_id;
    }

    /**
     * @return string[]
     */
    public function getAcceptableIssuerTenantIDs(): array
    {
        return $this->acceptable_tenant_for_authentication_configuration->getAcceptableIssuerTenantIDs();
    }

    public function getTenantSetup(): AzureADTenantSetup
    {
        return $this->acceptable_tenant_for_authentication_configuration->getTenantSetup();
    }

    public function getRedirectUri() : string
    {
        return 'https://' . ForgeConfig::get('sys_https_host') . '/plugins/openidconnectclient/azure/';
    }
}
