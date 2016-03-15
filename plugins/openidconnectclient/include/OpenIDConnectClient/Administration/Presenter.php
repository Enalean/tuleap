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

namespace Tuleap\OpenIDConnectClient\Administration;


class Presenter {
    /**
     * @var Provider[]
     */
    private $providers;
    /**
     * @var string
     */
    private $csrf_token;

    public function __construct(array $providers, $csrf_token) {
        $this->providers  = $providers;
        $this->csrf_token = $csrf_token;
    }

    public function title() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'title');
    }

    public function title_provider_configuration() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'title_provider_configuration');
    }

    public function name() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'name');
    }

    public function authorization_endpoint() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'authorization_endpoint');
    }

    public function token_endpoint() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'token_endpoint');
    }

    public function userinfo_endpoint() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'userinfo_endpoint');
    }

    public function client_id() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'client_id');
    }

    public function client_secret() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'client_secret');
    }

    public function add_new_provider() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'add_new_provider');
    }

    public function update_provider() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'update_provider');
    }

    public function btn_close() {
        return $GLOBALS['Language']->getText('global', 'btn_close');
    }

    public function btn_create() {
        return $GLOBALS['Language']->getText('global', 'btn_create');
    }

    public function btn_delete() {
        return $GLOBALS['Language']->getText('global', 'btn_delete');
    }

    public function btn_edit() {
        return $GLOBALS['Language']->getText('global', 'btn_edit');
    }

    public function btn_update() {
        return $GLOBALS['Language']->getText('global', 'btn_update');
    }

    public function providers() {
        return $this->providers;
    }

    public function csrf_token() {
        return $this->csrf_token;
    }

}