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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use System_Alias;
use Tuleap\ForgeConfigSandbox;

final class BackendAliasesTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $alias_file;
    private $backend;

    protected function setUp() : void
    {
        $GLOBALS['alias_file']         = vfsStream::setup()->url() . '/aliases.codendi';
        $this->alias_file              = $GLOBALS['alias_file'];
        $GLOBALS['codendi_bin_prefix'] = '/bin/';
        $GLOBALS['mailman_wrapper']    = 'mailman_wrapper';

        $listdao = \Mockery::spy(\MailingListDao::class);
        $listdao->shouldReceive('searchAllActiveML')->andReturns(
            \TestHelper::arrayToDar(
                ["list_name" => "list1"],
                ["list_name" => "list2"],
                ["list_name" => "list3"],
                ["list_name" => "list4"],
                ["list_name" => 'list with an unexpected quote "'],
                ["list_name" => "list with an unexpected newline\n"]
            )
        );

        $this->backend = \Mockery::mock(\BackendAliases::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->backend->shouldReceive('getMailingListDao')->andReturns($listdao);

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
        unset($GLOBALS['alias_file'], $GLOBALS['codendi_bin_prefix'], $GLOBALS['mailman_wrapper']);
        Backend::clearInstances();
        EventManager::clearInstance();
    }

    public function testItReturnsTrueInCaseOfSuccess() : void
    {
        $this->backend->shouldReceive('system')->andReturns(true);
        $this->assertEquals($this->backend->update(), true);
    }

    public function testItRunNewaliasesCommand() : void
    {
        $this->backend->shouldReceive('system')->with('/usr/bin/newaliases > /dev/null')->once();
        $this->backend->update();
    }

    public function testItGeneratesAnAliasesFile() : void
    {
        $this->backend->shouldReceive('system')->andReturns(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertFalse($aliases === false);
    }

    public function testItGenerateSiteWideAliases() : void
    {
        $this->backend->shouldReceive('system')->andReturns(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertStringContainsString('codendi-contact', $aliases, "Codendi-wide aliases not set");
    }

    public function testItGeneratesMailingListAliases() : void
    {
        $this->backend->shouldReceive('system')->andReturns(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertStringContainsString('"list1-bounces":', $aliases, "ML aliases not set");
        $this->assertStringContainsString('"listwithanunexpectedquote":', $aliases);
        $this->assertStringContainsString('"listwithanunexpectednewline":', $aliases);
    }

    public function testItGeneratesUserAliasesGivenByPlugins() : void
    {
        $this->backend->shouldReceive('system')->andReturns(true);
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertStringContainsString('"forge__tracker":', $aliases, "Alias of plugins not set");
    }
}
