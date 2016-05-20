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

class LoaderScheduler {

    private $cookie_manager;
    private $plugin_manager;

    public function __construct(CookieManager $cookie_manager, PluginManager $plugin_manager) {
        $this->cookie_manager = $cookie_manager;
        $this->plugin_manager = $plugin_manager;
    }

    public function loadPluginsThenStartSession($is_script) {
        $this->loadPlugins();
        $this->startSession($is_script);
    }

    private function loadPlugins() {
        $this->plugin_manager->loadPlugins();
    }

    private function startSession($is_script) {
        if (! $is_script) {
            $this->cookie_manager->configureSessionCookie();
            // Prevent "Pragma: no-cache" to be sent to user (break https & IE)
            session_cache_limiter(false);
            session_start();
            $GLOBALS['session_hash'] = $this->cookie_manager->isCookie('session_hash') ?
                $this->cookie_manager->getCookie('session_hash') : false;
        }
    }
}