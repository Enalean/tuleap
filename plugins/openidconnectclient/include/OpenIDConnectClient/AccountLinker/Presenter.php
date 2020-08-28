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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

use Tuleap\OpenIDConnectClient\Login\ConnectorPresenter;
use Tuleap\User\Account\AuthenticationMeanName;

class Presenter
{
    private $return_to;
    private $provider_name;
    private $link_to_register_page;
    private $is_registering_possible;
    /**
     * @var ConnectorPresenter
     * @psalm-readonly
     */
    public $provider_login_presenter;
    /**
     * @var string
     */
    private $authentication_mean_name;

    public function __construct(
        $return_to,
        $provider_name,
        $link_to_register_page,
        $is_registering_possible,
        ConnectorPresenter $provider_login_presenter,
        AuthenticationMeanName $authentication_mean_name
    ) {
        $this->return_to                = $return_to;
        $this->provider_name            = $provider_name;
        $this->link_to_register_page    = $link_to_register_page;
        $this->is_registering_possible  = $is_registering_possible;
        $this->provider_login_presenter = $provider_login_presenter;
        $this->authentication_mean_name = $authentication_mean_name->getName();
    }

    public function return_to()
    {
        return $this->return_to;
    }

    public function account_login_name()
    {
        return $GLOBALS['Language']->getOverridableText('account_login', 'name');
    }

    public function account_login_password()
    {
        return $GLOBALS['Language']->getText('account_login', 'password');
    }

    public function account_login_login_btn()
    {
        return $GLOBALS['Language']->getText('account_login', 'login_btn');
    }

    public function link_page_title()
    {
        return dgettext('tuleap-openidconnectclient', 'Link an account');
    }

    public function link_page_title_to()
    {
        return dgettext('tuleap-openidconnectclient', 'to OpenID Connect');
    }

    public function link_page_header_with_registration()
    {
        return sprintf(dgettext('tuleap-openidconnectclient', 'You have successfully been authenticated by %1$s. The only remaining step is to create a link with an existing %2$s account or register a new one.'), $this->provider_name, $this->authentication_mean_name);
    }

    public function link_page_header_without_registration()
    {
        return sprintf(dgettext('tuleap-openidconnectclient', 'You have successfully been authenticated by %1$s. The only remaining step is to create a link with an existing %2$s account.'), $this->provider_name, $this->authentication_mean_name);
    }

    public function action()
    {
        return OPENIDCONNECTCLIENT_BASE_URL . '/?action=link-existing';
    }

    public function or_label()
    {
        return dgettext('tuleap-openidconnectclient', 'or');
    }

    public function register()
    {
        return dgettext('tuleap-openidconnectclient', 'Register a new account');
    }

    public function link_to_register_page()
    {
        return $this->link_to_register_page;
    }

    public function is_registering_possible()
    {
        return $this->is_registering_possible;
    }
}
