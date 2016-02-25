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


use ForgeConfig;

class Presenter {

    private $link_id;
    private $return_to;
    private $provider_name;
    private $link_to_register_page;

    public function __construct($link_id, $return_to, $provider_name, $link_to_register_page) {
        $this->link_id               = $link_id;
        $this->return_to             = $return_to;
        $this->provider_name         = $provider_name;
        $this->link_to_register_page = $link_to_register_page;
    }

    public function link_id() {
        return $this->link_id;
    }

    public function return_to() {
        return $this->return_to;
    }

    public function account_login_name() {
        return $GLOBALS['Language']->getText('account_login', 'name');
    }

    public function account_login_password() {
        return $GLOBALS['Language']->getText('account_login', 'password');
    }

    public function account_login_login_btn() {
        return $GLOBALS['Language']->getText('account_login', 'login_btn');
    }

    public function link_login_with_tuleap() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'link_account_by_login');
    }

    public function link_page_title() {
        return $GLOBALS['Language']->getText(
            'plugin_openidconnectclient',
            'link_account',
            array(ForgeConfig::get('sys_name'))
        );
    }

    public function link_page_header() {
        return $GLOBALS['Language']->getText(
            'plugin_openidconnectclient',
            'link_page_header',
            array(
                $this->provider_name,
                ForgeConfig::get('sys_name')
            )
        );
    }

    public function action() {
        return OPENIDCONNECTCLIENT_BASE_URL . '/?action=link-existing';
    }

    public function register_new_account() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'register_new_account');
    }

    public function register() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient', 'register');
    }

    public function link_to_register_page() {
        return $this->link_to_register_page;
    }
}