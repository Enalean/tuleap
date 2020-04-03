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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\CookieManager;
use Tuleap\Plugin\PluginLoader;

final class LoaderSchedulerTest extends TestCase // phpcs:ignore
{
    use MockeryPHPUnitIntegration;
    use \Tuleap\TemporaryTestDirectory;

    /**
     * @var Mockery\MockInterface
     */
    private $cookie_manager;
    /**
     * @var Mockery\MockInterface|PluginLoader
     */
    private $plugin_loader;
    /**
     * @var LoaderScheduler
     */
    private $loader_scheduler;

    protected function setUp(): void
    {
        $this->cookie_manager = Mockery::mock(CookieManager::class);
        $this->plugin_loader = Mockery::mock(PluginLoader::class);

        $this->loader_scheduler = new LoaderScheduler($this->cookie_manager, $this->plugin_loader);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionHashLoading(): void
    {
        session_save_path($this->getTmpDir());

        $this->cookie_manager->shouldReceive('isCookie')->once()->andReturn(true);
        $this->cookie_manager->shouldReceive('getCookie')->once()->andReturn('test_value');
        $this->plugin_loader->shouldReceive('loadPlugins')->once();

        $this->loader_scheduler->loadPluginsThenStartSession(false);

        $this->assertSame('test_value', $GLOBALS['session_hash']);
    }

    public function testSessionIsNotLoadedWhenItsAScript(): void
    {
        $this->cookie_manager->shouldNotReceive('isCookie');
        $this->plugin_loader->shouldReceive('loadPlugins')->once();

        $this->loader_scheduler->loadPluginsThenStartSession(true);
    }
}
