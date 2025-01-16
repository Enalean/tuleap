<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PluginConfigChecker;

class SystemCheckTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $driver;
    private $gitgc;
    /** @var SystemCheck */
    private $system_check;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver   = \Mockery::spy(\Git_GitoliteDriver::class);
        $this->gitgc    = \Mockery::spy(\Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc::class);
        $logger         = \Mockery::spy(\Psr\Log\LoggerInterface::class);
        $config_checker = new PluginConfigChecker($logger);
        $plugin         = \Mockery::spy(\Plugin::class);

        $this->system_check = new SystemCheck(
            $this->gitgc,
            $this->driver,
            $config_checker,
            $plugin
        );
    }

    public function testItAsksToCheckAuthorizedKeys(): void
    {
        $this->driver->shouldReceive('checkAuthorizedKeys')->once();

        $this->system_check->process();
    }

    public function testItAsksToCleanUpGitoliteAdminRepository(): void
    {
        $this->gitgc->shouldReceive('cleanUpGitoliteAdminWorkingCopy')->once();

        $this->system_check->process();
    }
}
