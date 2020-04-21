<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */
declare(strict_types=1);

namespace Tuleap\Docman;

use BackendSystem;
use Docman_SystemCheck;
use Docman_SystemCheckProjectRetriever;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plugin;
use PluginConfigChecker;
use Psr\Log\LoggerInterface;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_SystemCheckTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Docman_SystemCheck */
    private $system_check;

    /** @var Plugin */
    private $plugin;

    /** @var Docman_SystemCheckProjectRetriever */
    private $retriever;
    /**
     * @var BackendSystem|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $backend;

    public function setUp(): void
    {
        parent::setUp();

        ForgeConfig::store();
        ForgeConfig::set('sys_http_user', 'codendiadm');

        $this->plugin = Mockery::mock(Plugin::class);
        $this->plugin->shouldReceive('getServiceShortname')->andReturn('docman');
        $this->retriever = \Mockery::spy(\Docman_SystemCheckProjectRetriever::class);
        $logger          = \Mockery::spy(LoggerInterface::class);
        $config_checker  = new PluginConfigChecker($logger);
        $this->backend   = Mockery::mock(BackendSystem::class);

        $this->root_dir_path = vfsStream::setup()->url();

        $plugin_info = \Mockery::mock(\DocmanPluginInfo::class);
        $plugin_info->shouldReceive('getPropertyValueForName')->with('docman_root')->andReturns($this->root_dir_path);
        $this->plugin->shouldReceive('getPluginInfo')->andReturns($plugin_info);
        $this->plugin->shouldReceive('getPluginEtcRoot')->andReturns(ForgeConfig::get('codendi_cache_dir'));

        $this->system_check = new Docman_SystemCheck(
            $this->plugin,
            $this->retriever,
            $this->backend,
            $config_checker,
            $logger
        );
    }

    public function tearDown(): void
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function testItCreatesFolderForActiveProject(): void
    {
        $this->retriever->shouldReceive('getActiveProjectUnixNamesThatUseDocman')->andReturns(
            array('project_01')
        );

        $this->backend->shouldReceive('changeOwnerGroupMode')->once();
        $this->system_check->process();

        $this->assertTrue(is_dir($this->root_dir_path . '/project_01'));
    }
}
