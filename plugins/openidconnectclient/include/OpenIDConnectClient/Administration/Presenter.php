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

use ForgeConfig;

class Presenter {
    /**
     * @var ProviderPresenter[]
     */
    public $providers_presenters;

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

    const TEMPLATE = 'administration-providers';

    public function __construct(
        array $providers_presenters,
        array $icons_presenters,
        array $colors_presenters,
        $csrf_token
    ) {
        $this->providers_presenters = $providers_presenters;
        $this->icons_presenters     = $icons_presenters;
        $this->colors_presenters    = $colors_presenters;
        $this->csrf_token           = $csrf_token;
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

    public function client_help() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'client_help');
    }

    public function callback_url() {
        $host = urlencode(ForgeConfig::get('sys_default_domain'));

        return strtolower('https://' . $host . OPENIDCONNECTCLIENT_BASE_URL . '/');
    }

    public function icon() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'icon');
    }

    public function color() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'color');
    }

    public function preview() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'preview');
    }

    public function unique_authentication_source()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'unique_authentication_source');
    }

    public function active()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'active');
    }

    public function inactive()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'inactive');
    }

    public function unique_authentication_source_form_description()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'unique_authentication_source_form');
    }

    public function unique_authentication_source_prerequisites()
    {
        return $GLOBALS['Language']->getText(
            'plugin_openidconnectclient_admin',
            'unique_authentication_source_prerequisites'
        );
    }

    public function unique_authentication_source_user_must_be_linked()
    {
        return $GLOBALS['Language']->getText(
            'plugin_openidconnectclient_admin',
            'unique_authentication_source_user_must_be_linked'
        );
    }

    public function add_new_provider() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'add_new_provider');
    }

    public function update_provider() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'update_provider');
    }

    public function https_placeholder() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'https_placeholder');
    }

    public function btn_cancel() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_cancel');
    }

    public function btn_create() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_create');
    }

    public function btn_delete() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_delete');
    }

    public function btn_edit() {
        return $GLOBALS['Language']->getText('global', 'btn_edit');
    }

    public function btn_update() {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_update');
    }

    public function delete_modal_title()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'delete_modal_title');
    }

    public function delete_modal_content()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'delete_modal_content');
    }

    public function btn_close()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_close');
    }

    public function delete_modal_submit()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'delete_modal_submit');
    }

    public function there_are_providers()
    {
        return count($this->providers_presenters)  > 0;
    }

    public function empty_content()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'empty_content');
    }

    public function empty_providers_text_start()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'empty_providers_text_start');
    }

    public function empty_providers_text_end()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'empty_providers_text_end');
    }

    public function btn_preview()
    {
        return $GLOBALS['Language']->getText('plugin_openidconnectclient_admin', 'btn_preview');
    }
}
