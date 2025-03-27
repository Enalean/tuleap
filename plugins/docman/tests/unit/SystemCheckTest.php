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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Plugin;
use PluginConfigChecker;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemCheckTest extends TestCase
{
    use ForgeConfigSandbox;

    private Docman_SystemCheck $system_check;
    private Docman_SystemCheckProjectRetriever&MockObject $retriever;
    private BackendSystem&MockObject $backend;
    private string $root_dir_path;

    public function setUp(): void
    {
        ForgeConfig::set('sys_http_user', 'codendiadm');

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getServiceShortname')->willReturn('docman');
        $this->retriever = $this->createMock(Docman_SystemCheckProjectRetriever::class);
        $logger          = new NullLogger();
        $config_checker  = new PluginConfigChecker($logger);
        $this->backend   = $this->createMock(BackendSystem::class);

        $this->root_dir_path = vfsStream::setup()->url();

        ForgeConfig::set(\DocmanPlugin::CONFIG_ROOT_DIRECTORY, $this->root_dir_path);
        $plugin->method('getPluginEtcRoot')->willReturn(ForgeConfig::get('codendi_cache_dir'));

        $this->system_check = new Docman_SystemCheck(
            $plugin,
            $this->retriever,
            $this->backend,
            $config_checker,
            $logger
        );
    }

    public function testItCreatesFolderForActiveProject(): void
    {
        $this->retriever->method('getActiveProjectUnixNamesThatUseDocman')->willReturn(['project_01']);

        $this->backend->expects($this->once())->method('changeOwnerGroupMode');
        $this->system_check->process();

        self::assertTrue(is_dir($this->root_dir_path . '/project_01'));
    }
}
