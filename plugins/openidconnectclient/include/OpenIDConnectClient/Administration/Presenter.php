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

use ForgeConfig;

class Presenter
{
    /**
     * @var GenericProviderPresenter[]
     */
    public $providers_presenters;

    /**
     * @var bool
     */
    public $is_a_provider_configured_as_unique_authentication_endpoint;

    /**
     * @var IconPresenter[]
     */
    public $icons_presenters;

    /**
     * @var ColorPresenter[]
     */
    public $colors_presenters;

    /**
     * @var string
     */
    public $csrf_token;
    public const TEMPLATE = 'administration-providers';
    /**
     * @var AzureADTenantSetupPresenter[]
     */
    public $azure_ad_tenant_setups;

    /**
     * @param AzureADTenantSetupPresenter[] $azure_ad_tenant_setups
     */
    public function __construct(
        array $providers_presenters,
        $is_a_provider_configured_as_unique_authentication_endpoint,
        array $icons_presenters,
        array $colors_presenters,
        array $azure_ad_tenant_setups,
        $csrf_token
    ) {
        $this->providers_presenters                                       = $providers_presenters;
        $this->is_a_provider_configured_as_unique_authentication_endpoint = $is_a_provider_configured_as_unique_authentication_endpoint;
        $this->icons_presenters                                           = $icons_presenters;
        $this->colors_presenters                                          = $colors_presenters;
        $this->azure_ad_tenant_setups                                     = $azure_ad_tenant_setups;
        $this->csrf_token                                                 = $csrf_token;
    }

    public function title()
    {
        return dgettext('tuleap-openidconnectclient', 'OpenID Connect');
    }

    public function title_provider_configuration()
    {
        return dgettext('tuleap-openidconnectclient', 'OpenID Connect providers');
    }

    public function name()
    {
        return dgettext('tuleap-openidconnectclient', 'Name');
    }

    public function authorization_endpoint()
    {
        return dgettext('tuleap-openidconnectclient', 'Authorization endpoint');
    }

    public function token_endpoint()
    {
        return dgettext('tuleap-openidconnectclient', 'Token endpoint');
    }

    public function userinfo_endpoint()
    {
        return dgettext('tuleap-openidconnectclient', 'User information endpoint');
    }

    public function client_id()
    {
        return dgettext('tuleap-openidconnectclient', 'Client ID');
    }

    public function client_secret()
    {
        return dgettext('tuleap-openidconnectclient', 'Client secret');
    }

    public function client_help()
    {
        return dgettext('tuleap-openidconnectclient', 'Following information have to be generated on the OpenID Connect provider side. You\'ll need to give a callback url to do that, here it is:');
    }

    public function callback_generic_url()
    {
        $host = urlencode(ForgeConfig::get('sys_default_domain'));

        return strtolower('https://' . $host . OPENIDCONNECTCLIENT_BASE_URL . '/');
    }

    public function callback_azure_url()
    {
        $host = urlencode(ForgeConfig::get('sys_default_domain'));

        return strtolower('https://' . $host . OPENIDCONNECTCLIENT_BASE_URL . '/azure/');
    }

    public function icon()
    {
        return dgettext('tuleap-openidconnectclient', 'Icon');
    }

    public function color()
    {
        return dgettext('tuleap-openidconnectclient', 'Color');
    }

    public function preview()
    {
        return dgettext('tuleap-openidconnectclient', 'Preview of the login page button');
    }

    public function unique_authentication_source()
    {
        return dgettext('tuleap-openidconnectclient', 'Unique authentication source');
    }

    public function unique_authentication_source_disabled()
    {
        return dgettext('tuleap-openidconnectclient', 'Disabled');
    }

    public function unique_authentication_source_form_description()
    {
        return dgettext('tuleap-openidconnectclient', 'This provider is the only authentication method.');
    }

    public function unique_authentication_source_prerequisites()
    {
        return dgettext('tuleap-openidconnectclient', 'Be sure that your provider can provide the profile and email scopes otherwise users will not have an account created automatically.');
    }

    public function unique_authentication_source_user_must_be_linked()
    {
        return dgettext('tuleap-openidconnectclient', 'You can only enable this provider as unique authentication method if you are yourself linked to it.');
    }

    public function update_provider()
    {
        return dgettext('tuleap-openidconnectclient', 'Edit provider');
    }

    public function https_placeholder()
    {
        return dgettext('tuleap-openidconnectclient', 'https://');
    }

    public function btn_cancel()
    {
        return dgettext('tuleap-openidconnectclient', 'Cancel');
    }

    public function btn_create()
    {
        return dgettext('tuleap-openidconnectclient', 'Create the provider');
    }

    public function btn_delete()
    {
        return dgettext('tuleap-openidconnectclient', 'Delete');
    }

    public function btn_edit()
    {
        return $GLOBALS['Language']->getText('global', 'btn_edit');
    }

    public function btn_update()
    {
        return dgettext('tuleap-openidconnectclient', 'Update the provider');
    }

    public function delete_modal_title()
    {
        return dgettext('tuleap-openidconnectclient', 'Delete a provider');
    }

    public function delete_modal_content()
    {
        return dgettext('tuleap-openidconnectclient', 'You are about to delete a provider. This action is irreversible. Do you confirm this deletion?');
    }

    public function btn_close()
    {
        return dgettext('tuleap-openidconnectclient', 'Cancel');
    }

    public function delete_modal_submit()
    {
        return dgettext('tuleap-openidconnectclient', 'Delete');
    }

    public function there_are_providers()
    {
        return count($this->providers_presenters)  > 0;
    }

    public function empty_content()
    {
        return dgettext('tuleap-openidconnectclient', 'Empty');
    }

    public function empty_providers_text_start()
    {
        return dgettext('tuleap-openidconnectclient', 'There is nothing here,');
    }

    public function empty_providers_text_end()
    {
        return dgettext('tuleap-openidconnectclient', 'start by adding a provider.');
    }

    public function btn_preview()
    {
        return dgettext('tuleap-openidconnectclient', 'Preview');
    }
}
