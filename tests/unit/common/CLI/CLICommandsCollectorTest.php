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

namespace Tuleap\CLI;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CLICommandsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testALoadedCommandCanBeFoundInTheApplication(): void
    {
        $commands_collector = new CLICommandsCollector();
        $commands_collector->addCommand(
            'testcommand',
            static function (): Command {
                return new class extends Command {
                    public function __construct()
                    {
                        parent::__construct('testcommand');
                    }
                };
            }
        );

        $application = new Application();
        $commands_collector->loadCommands($application);

        $this->assertTrue($application->has('testcommand'));
    }
}
