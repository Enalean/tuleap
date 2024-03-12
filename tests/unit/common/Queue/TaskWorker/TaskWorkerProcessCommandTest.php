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

namespace Tuleap\Queue\TaskWorker;

use JsonException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Test\Stubs\Queue\FindWorkerEventProcessorStub;
use Tuleap\Test\Stubs\Queue\WorkerEventProcessorStub;

final class TaskWorkerProcessCommandTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private vfsStreamDirectory $filesystem_root;

    protected function setUp(): void
    {
        $this->filesystem_root = vfsStream::setup();
    }

    public function testKnownEventIsProcessed(): void
    {
        $processor = WorkerEventProcessorStub::build();

        $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
        $command          = new TaskWorkerProcessCommand($event_dispatcher, new NullLogger(), FindWorkerEventProcessorStub::withMatchingProcessor($processor));

        $path_to_file            = $this->filesystem_root->url() . '/event';
        $event_serialized_string = '{"event_name":"event.name","payload":{"id":2545}}';
        file_put_contents($path_to_file, $event_serialized_string);

        $event_dispatcher->expects(self::never())->method('dispatch');

        $command_tester = new CommandTester($command);
        $command_tester->execute(['input_file' => $path_to_file]);

        self::assertTrue($processor->isProcessed());
    }

    public function testEventIsDispatchedForProcessing(): void
    {
        $event_dispatcher = $this->createMock(EventDispatcherInterface::class);
        $command          = new TaskWorkerProcessCommand($event_dispatcher, new NullLogger(), FindWorkerEventProcessorStub::withoutProcessor());

        $path_to_file            = $this->filesystem_root->url() . '/event';
        $event_serialized_string = '{"event_name":"event.name","payload":{"id":2545}}';
        file_put_contents($path_to_file, $event_serialized_string);

        $event_dispatcher->expects(self::atLeastOnce())->method('dispatch')->with(self::isInstanceOf(WorkerEvent::class));

        $command_tester = new CommandTester($command);
        $command_tester->execute(['input_file' => $path_to_file]);
    }

    public function testEventNotProperlyJSONSerializedIsRejected(): void
    {
        $command = new TaskWorkerProcessCommand($this->createMock(EventDispatcherInterface::class), new NullLogger(), FindWorkerEventProcessorStub::withoutProcessor());

        $path_to_file = $this->filesystem_root->url() . '/event';
        file_put_contents($path_to_file, '{ broken json');

        $command_tester = new CommandTester($command);

        self::expectException(JsonException::class);
        $command_tester->execute(['input_file' => $path_to_file]);
    }
}
