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

use Git_GitoliteDriver;
use Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc;
use PHPUnit\Framework\MockObject\MockObject;
use Plugin;
use PluginConfigChecker;
use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SystemCheckTest extends TestCase
{
    private Git_GitoliteDriver&MockObject $driver;
    private Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc&MockObject $gitgc;
    private SystemCheck $system_check;
    private Plugin&MockObject $plugin;

    protected function setUp(): void
    {
        $this->driver       = $this->createMock(Git_GitoliteDriver::class);
        $this->gitgc        = $this->createMock(Git_GitoliteHousekeeping_GitoliteHousekeepingGitGc::class);
        $this->plugin       = $this->createMock(Plugin::class);
        $this->system_check = new SystemCheck(
            $this->gitgc,
            $this->driver,
            new PluginConfigChecker(new NullLogger()),
            $this->plugin,
        );
    }

    public function testItAsksToCheckAuthorizedKeysAndToCleanUpGitoliteAdminRepository(): void
    {
        $this->driver->expects($this->once())->method('checkAuthorizedKeys');
        $this->gitgc->expects($this->once())->method('cleanUpGitoliteAdminWorkingCopy');
        $this->plugin->method('getPluginEtcRoot')->willReturn('/do/not/exist');

        $this->system_check->process();
    }
}
