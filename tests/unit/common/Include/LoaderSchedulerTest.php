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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tuleap\CookieManager;
use Tuleap\Plugin\PluginLoader;

final class LoaderSchedulerTest extends TestCase // phpcs:ignore
{
    use \Tuleap\TemporaryTestDirectory;

    /**
     * @var CookieManager&MockObject
     */
    private $cookie_manager;
    /**
     * @var PluginLoader&MockObject
     */
    private $plugin_loader;
    /**
     * @var LoaderScheduler
     */
    private $loader_scheduler;

    protected function setUp(): void
    {
        $this->cookie_manager = $this->createMock(CookieManager::class);
        $this->plugin_loader  = $this->createMock(PluginLoader::class);

        $this->loader_scheduler = new LoaderScheduler($this->cookie_manager, $this->plugin_loader);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionHashLoading(): void
    {
        session_save_path($this->getTmpDir());

        $this->cookie_manager->expects(self::once())->method('isCookie')->willReturn(true);
        $this->cookie_manager->expects(self::once())->method('getCookie')->willReturn('test_value');
        $this->plugin_loader->expects(self::once())->method('loadPlugins');

        $this->loader_scheduler->loadPluginsThenStartSession(false, []);

        self::assertSame('test_value', $GLOBALS['session_hash']);
    }

    public function testSessionIsNotLoadedWhenItsAScript(): void
    {
        $this->cookie_manager->expects(self::never())->method('isCookie');
        $this->plugin_loader->expects(self::once())->method('loadPlugins');

        $this->loader_scheduler->loadPluginsThenStartSession(true, []);
    }

    public function testSessionIsNotLoadedWhenAFastCgiParamIsSetToDisableIt(): void
    {
        $this->cookie_manager->expects(self::never())->method('isCookie');
        $this->plugin_loader->expects(self::once())->method('loadPlugins');

        $this->loader_scheduler->loadPluginsThenStartSession(false, ['TULEAP_DISABLE_AUTO_SESSION_START' => 'true']);
    }
}
