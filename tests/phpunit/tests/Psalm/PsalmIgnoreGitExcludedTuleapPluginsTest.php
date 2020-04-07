<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use System_Command_CommandException;

final class PsalmIgnoreGitExcludedTuleapPluginsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testOnlyPluginsAreExcluded(): void
    {
        $system_command = \Mockery::mock(\System_Command::class);
        $system_command->shouldReceive('exec')->with(\Mockery::pattern('#plugins/\'\*$#'))->once()->andReturn([]);

        $ignore_directory = new PsalmIgnoreGitExcludedTuleapPlugins($system_command);
        $this->assertEmpty($ignore_directory->getIgnoredDirectories());
    }

    public function testWhenNoPluginsIsExcludedItDoesNotCrash(): void
    {
        $system_command = \Mockery::mock(\System_Command::class);
        $system_command->shouldReceive('exec')->andThrow(
            new System_Command_CommandException('command', ['output'], 1)
        );

        $ignore_directory = new PsalmIgnoreGitExcludedTuleapPlugins($system_command);
        $this->assertEmpty($ignore_directory->getIgnoredDirectories());
    }

    public function testGitFatalErrorsAreNotHidden(): void
    {
        $system_command = \Mockery::mock(\System_Command::class);
        $system_command->shouldReceive('exec')->andThrow(
            new System_Command_CommandException('command', ['output'], 128)
        );

        $ignore_directory = new PsalmIgnoreGitExcludedTuleapPlugins($system_command);
        $this->expectException(System_Command_CommandException::class);
        $ignore_directory->getIgnoredDirectories();
    }
}
