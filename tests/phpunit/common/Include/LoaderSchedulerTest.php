<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

class LoaderSchedulerTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface
     */
    private $cookie_manager;
    /**
     * @var Mockery\MockInterface
     */
    private $plugin_manager;
    /**
     * @var LoaderScheduler
     */
    private $loader_scheduler;

    protected function setUp()
    {
        $this->cookie_manager = Mockery::mock(CookieManager::class);
        $this->plugin_manager = Mockery::mock(PluginManager::class);

        $this->loader_scheduler = new LoaderScheduler($this->cookie_manager, $this->plugin_manager);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSessionHashLoading()
    {
        $this->cookie_manager->shouldReceive('isCookie')->once()->andReturn(true);
        $this->cookie_manager->shouldReceive('getCookie')->once()->andReturn('test_value');
        $this->plugin_manager->shouldReceive('loadPlugins')->once();

        $this->loader_scheduler->loadPluginsThenStartSession(false);

        $this->assertSame('test_value', $GLOBALS['session_hash']);
    }

    public function testSessionIsNotLoadedWhenItsAScript()
    {
        $this->cookie_manager->shouldNotReceive('isCookie');
        $this->plugin_manager->shouldReceive('loadPlugins')->once();

        $this->loader_scheduler->loadPluginsThenStartSession(true);
    }
}
