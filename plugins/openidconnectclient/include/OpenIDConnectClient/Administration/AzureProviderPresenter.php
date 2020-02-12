<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Administration;

use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADProvider;
use Tuleap\OpenIDConnectClient\Provider\AzureADProvider\AzureADTenantSetup;

class AzureProviderPresenter
{
    /**
     * @var AzureADProvider
     */
    private $provider;

    /**
     * @var IconPresenter[]
     */
    public $icons_presenters;

    /**
     * @var ColorPresenter[]
     */
    public $colors_presenters;

    /**
     * @var AzureADTenantSetupPresenter[]
     */
    public $azure_ad_tenant_setups;

    /**
     * @var bool
     */
    public $can_user_enable_unique_authentication_endpoint;
    /**
     * @var string
     */
    public $description_users_allowed_to_connect;

    public function __construct(
        AzureADProvider $provider,
        $can_user_enable_unique_authentication_endpoint,
        array $icons_presenters,
        array $colors_presenters
    ) {
        $this->provider                                       = $provider;
        $this->can_user_enable_unique_authentication_endpoint = $can_user_enable_unique_authentication_endpoint;
        $this->icons_presenters                               = $icons_presenters;
        $this->colors_presenters                              = $colors_presenters;
        $tenant_setup                                         = $provider->getTenantSetup();
        $this->azure_ad_tenant_setups                         = AzureADTenantSetupPresenter::fromAllAcceptableValues(
            AzureADTenantSetup::allPossibleSetups(),
            $tenant_setup
        );
        $this->description_users_allowed_to_connect           = $tenant_setup->getDescription();
    }

    public function getId()
    {
        return $this->provider->getId();
    }

    public function getName()
    {
        return $this->provider->getName();
    }

    public function getClientId()
    {
        return $this->provider->getClientId();
    }

    public function getClientSecret()
    {
        return $this->provider->getClientSecret();
    }

    public function getIcon()
    {
        return $this->provider->getIcon();
    }

    public function getColor()
    {
        return $this->provider->getColor();
    }

    public function isUniqueAuthenticationEndpoint()
    {
        return $this->provider->isUniqueAuthenticationEndpoint();
    }

    public function getTenantId()
    {
        return $this->provider->getTenantId();
    }

    public function isAzureAD()
    {
        return true;
    }

    public function getCallbackUrl()
    {
        return strtolower($this->provider->getRedirectUri());
    }
}
