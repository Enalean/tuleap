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

namespace Tuleap\Tracker\Workflow;

use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class WorkflowBackendLoggerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var LoggerInterface|Mockery\MockInterface
     */
    private $backend_logger;

    protected function setUp(): void
    {
        $this->backend_logger = Mockery::mock(\Psr\Log\LoggerInterface::class);
    }

    public function testLogsMethod(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] ┌ Start theMethod()', [])->once();
        $logger->start('theMethod');
    }

    public function testLogsOptionalArgument(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] ┌ Start theMethod(1, a)', [])->once();
        $logger->start('theMethod', 1, 'a');
    }

    public function testWorksAlsoWorksForEndMethod(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] └ End theMethod(1, a)', [])->once();
        $logger->end('theMethod', 1, 'a');
    }

    public function testIncrementsOnStartAndDecrementsOnEnd(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] ┌ Start method()', [])->once();
        $this->backend_logger->shouldReceive('debug')->with('[WF] │ ┌ Start subMethod()', [])->once();
        $this->backend_logger->shouldReceive('debug')->with('[WF] │ └ End subMethod()', [])->once();
        $this->backend_logger->shouldReceive('debug')->with('[WF] └ End method()', [])->once();
        $logger->start('method');
        $logger->start('subMethod');
        $logger->end('subMethod');
        $logger->end('method');
    }

    public function testIncludesTheFingerprint(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] [12345] toto', [])->once();
        $logger->defineFingerprint(12345);
        $logger->debug('toto');
    }

    public function testDoesNotChangeTheFingerprint(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::DEBUG);
        $this->backend_logger->shouldReceive('debug')->with('[WF] [12345] toto', [])->once();
        $logger->defineFingerprint(12345);
        $logger->defineFingerprint(67890);
        $logger->debug('toto');
    }

    public function testLogLevelIsRespected(): void
    {
        $logger = new WorkflowBackendLogger($this->backend_logger, \Psr\Log\LogLevel::INFO);
        $this->backend_logger->shouldReceive('debug')->never();
        $logger->start('theMethod');
    }
}
