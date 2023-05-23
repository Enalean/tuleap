<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\PluginsAdministration\LifecycleHookCommand;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use ColinODell\PsrTestLogger\TestLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\CLI\AssertRunner;
use Tuleap\Plugin\LifecycleHookCommand\PluginExecuteUpdateHookEvent;
use Tuleap\Test\PHPUnit\TestCase;

final class PluginUpdateHookCommandTest extends TestCase
{
    public function testLaunchesEvent(): void
    {
        $event_dispatcher = new class implements EventDispatcherInterface {
            public bool $has_been_called_with_expected_event = false;

            public function dispatch(object $event): object
            {
                if ($event instanceof  PluginExecuteUpdateHookEvent) {
                    $this->has_been_called_with_expected_event = true;
                }
                return $event;
            }
        };

        $command_tester = new CommandTester(new PluginUpdateHookCommand($event_dispatcher, AssertRunner::asCurrentProcessUser(), new NullLogger()));

        $command_tester->execute([]);

        self::assertTrue($event_dispatcher->has_been_called_with_expected_event);
    }

    public function testLogsErrorWhenSomethingBadHappen(): void
    {
        $event_dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                throw new \Exception("Something bad");
            }
        };

        $logger         = new TestLogger();
        $command_tester = new CommandTester(new PluginUpdateHookCommand($event_dispatcher, AssertRunner::asCurrentProcessUser(), $logger));

        $this->expectException(\Exception::class);
        try {
            $command_tester->execute([]);
        } catch (\Exception $exception) {
            self::assertTrue($logger->hasErrorRecords());
            throw $exception;
        }
    }
}
