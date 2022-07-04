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

use Tuleap\CookieManager;
use Tuleap\Plugin\PluginLoader;

class LoaderScheduler
{
    private const FASTCGI_DISABLE_SESSION_AUTOSTART_INSTRUCTION = 'TULEAP_DISABLE_AUTO_SESSION_START';

    private $cookie_manager;
    /**
     * @var PluginLoader
     */
    private $plugin_loader;

    public function __construct(CookieManager $cookie_manager, PluginLoader $plugin_loader)
    {
        $this->cookie_manager = $cookie_manager;
        $this->plugin_loader  = $plugin_loader;
    }

    public function loadPluginsThenStartSession($is_script, array $server): void
    {
        $this->plugin_loader->loadPlugins();
        $this->startSession($is_script, $server);
    }

    private function startSession($is_script, array $server): void
    {
        if ($is_script) {
            return;
        }

        if (($server[self::FASTCGI_DISABLE_SESSION_AUTOSTART_INSTRUCTION] ?? '') === 'true') {
            return;
        }

        PHP_Session::start();
        $GLOBALS['session_hash'] = $this->cookie_manager->isCookie('session_hash') ?
            $this->cookie_manager->getCookie('session_hash') : false;
    }
}
