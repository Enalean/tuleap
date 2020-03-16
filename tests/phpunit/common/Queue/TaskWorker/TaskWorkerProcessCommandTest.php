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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Queue\WorkerEvent;

final class TaskWorkerProcessCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var \org\bovigo\vfs\vfsStreamDirectory
     */
    private $filesystem_root;

    protected function setUp() : void
    {
        $this->filesystem_root = vfsStream::setup();
    }

    public function testEventIsDispatchedForProcessing() : void
    {
        $event_dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $logger           = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $command          = new TaskWorkerProcessCommand($event_dispatcher, $logger);

        $path_to_file            = $this->filesystem_root->url() . '/event';
        $event_serialized_string = '{"event_name":"event.name","payload":{"id":2545}}';
        file_put_contents($path_to_file, $event_serialized_string);

        $logger->shouldReceive('debug')->with(Mockery::on(static function (string $message) use ($event_serialized_string) : bool {
            return strpos($message, $event_serialized_string) !== false;
        }));
        $event_dispatcher->shouldReceive('dispatch')->with(Mockery::type(WorkerEvent::class));

        $command_tester = new CommandTester($command);
        $command_tester->execute(['input_file' => $path_to_file]);
    }

    public function testEventNotProperlyJSONSerializedIsRejected() : void
    {
        $logger           = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $command          = new TaskWorkerProcessCommand(Mockery::mock(EventDispatcherInterface::class), $logger);

        $path_to_file            = $this->filesystem_root->url() . '/event';
        file_put_contents($path_to_file, '{ broken json');

        $logger->shouldReceive('debug')->atLeast()->once();

        $command_tester = new CommandTester($command);

        $this->expectException(JsonException::class);
        $command_tester->execute(['input_file' => $path_to_file]);
    }
}
