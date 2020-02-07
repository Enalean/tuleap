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
use Tracker_Artifact;

final class WorkflowRulesManagerLoopSafeGuardTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testAnArtifactIsProcessedOnlyOnceWhenARecursiveLoopIsDetectedInTheProcess() : void
    {
        $logger          = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $workflow_logger = new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::ERROR);

        $logger->shouldReceive('error');

        $guard = new WorkflowRulesManagerLoopSafeGuard($workflow_logger);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);

        $process = new class($guard, $artifact) {
            /**
             * @var int
             */
            public $nb_executions = 0;
            /**
             * @var WorkflowRulesManagerLoopSafeGuard
             */
            private $guard;
            /**
             * @var Tracker_Artifact
             */
            private $artifact;

            public function __construct(WorkflowRulesManagerLoopSafeGuard $guard, Tracker_Artifact $artifact)
            {
                $this->guard    = $guard;
                $this->artifact = $artifact;
            }

            public function __invoke() : void
            {
                $this->nb_executions++;
                $this->guard->process($this->artifact, $this);
            }
        };

        $guard->process($artifact, $process);
        $this->assertEquals(1, $process->nb_executions);
    }

    public function testAnArtifactCanBeProcessedSequentiallyMultipleTimes() : void
    {
        $logger          = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $workflow_logger = new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::ERROR);

        $logger->shouldNotReceive('error');

        $guard = new WorkflowRulesManagerLoopSafeGuard($workflow_logger);

        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(123);

        $process = new class {
            /**
             * @var int
             */
            public $nb_executions = 0;

            public function __invoke() : void
            {
                $this->nb_executions++;
            }
        };

        $guard->process($artifact, $process);
        $guard->process($artifact, $process);
        $guard->process($artifact, $process);
        $this->assertEquals(3, $process->nb_executions);
    }
}
