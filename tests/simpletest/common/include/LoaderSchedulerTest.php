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
class LoaderSchedulerTest extends TuleapTestCase {
    private $untouched_session_hash_value;

    public function setUp() {
        parent::setUp();
        $this->untouched_session_hash_value  = isset($GLOBALS['session_hash']) ? $GLOBALS['session_hash'] : false;
        set_error_handler('LoaderSchedulerTest::errorHandlerCatchSessionStartedNotice', (E_ALL | E_STRICT) & ~E_RECOVERABLE_ERROR);
    }

    public function tearDown() {
        $GLOBALS['session_hash'] = $this->untouched_session_hash_value;
        restore_error_handler();
        parent::tearDown();
    }

    public static function errorHandlerCatchSessionStartedNotice($error_number , $error_string) {
        if ($error_string !== 'A session had already been started - ignoring session_start()') {
            return false;
        }
    }

    public function itLoadsSessionHash() {
        $cookie_manager   = mock('CookieManager');
        $plugin_manager   = mock('PluginManager');
        $loader_scheduler = new LoaderScheduler($cookie_manager, $plugin_manager);

        $cookie_manager->expectOnce('isCookie');
        $loader_scheduler->loadPluginsThenStartSession(false);
    }

    public function itDoesNotLoadSessionWhenItsAScript() {
        $cookie_manager   = mock('CookieManager');
        $plugin_manager   = mock('PluginManager');
        $loader_scheduler = new LoaderScheduler($cookie_manager, $plugin_manager);

        $cookie_manager->expectNever('isCookie');
        $loader_scheduler->loadPluginsThenStartSession(true);
    }

    public function itAlwaysLoadPlugins() {
        $cookie_manager   = mock('CookieManager');
        $plugin_manager   = mock('PluginManager');
        $loader_scheduler = new LoaderScheduler($cookie_manager, $plugin_manager);

        $plugin_manager->expectCallCount('loadPlugins', 2);
        $loader_scheduler->loadPluginsThenStartSession(true);
        $loader_scheduler->loadPluginsThenStartSession(false);
    }

}