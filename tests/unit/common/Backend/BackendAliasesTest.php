<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2011 - Present. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Backend;

use Backend;
use Event;
use EventManager;
use org\bovigo\vfs\vfsStream;
use System_Alias;
use Tuleap\ForgeConfigSandbox;

final class BackendAliasesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private $alias_file;
    private $backend;

    protected function setUp(): void
    {
        \ForgeConfig::set('alias_file', vfsStream::setup()->url() . '/aliases.codendi');
        $this->alias_file = \ForgeConfig::get('alias_file');
        \ForgeConfig::set('codendi_bin_prefix', '/bin/');

        $this->backend = $this->createPartialMock(\BackendAliases::class, [
            'system',
        ]);

        $plugin = new class {
            public function hook(array $params): void
            {
                $params['aliases'][] = new System_Alias('forge__tracker', 'whatever');
            }
        };
        EventManager::instance()->addListener(
            Event::BACKEND_ALIAS_GET_ALIASES,
            $plugin,
            'hook',
            false
        );
    }

    protected function tearDown(): void
    {
        Backend::clearInstances();
        EventManager::clearInstance();
    }

    public function testItReturnsTrueInCaseOfSuccess(): void
    {
        $this->backend->method('system')->willReturn(true);
        self::assertTrue($this->backend->update());
    }

    public function testItRunNewaliasesCommand(): void
    {
        $this->backend->expects(self::once())->method('system')->with('/usr/bin/newaliases > /dev/null');
        $this->backend->update();
    }

    public function testItGeneratesAnAliasesFile(): void
    {
        $this->backend->method('system')->willReturn(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        self::assertFalse($aliases === false);
    }

    public function testItGenerateSiteWideAliases(): void
    {
        $this->backend->method('system')->willReturn(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        self::assertStringContainsString('codendi-contact', $aliases, "Codendi-wide aliases not set");
    }

    public function testItGeneratesUserAliasesGivenByPlugins(): void
    {
        $this->backend->method('system')->willReturn(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        self::assertStringContainsString('"forge__tracker":', $aliases, "Alias of plugins not set");
    }
}
