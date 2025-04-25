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

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorkflowRulesManagerLoopSafeGuardTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAnArtifactIsProcessedOnlyOnceWhenARecursiveLoopIsDetectedInTheProcess(): void
    {
        $logger          = new TestLogger();
        $workflow_logger = new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::ERROR);

        $guard = new WorkflowRulesManagerLoopSafeGuard($workflow_logger);

        $artifact = ArtifactTestBuilder::anArtifact(123)->build();

        $process = new class ($guard, $artifact) {
            /**
             * @var int
             */
            public $nb_executions = 0;
            /**
             * @var WorkflowRulesManagerLoopSafeGuard
             */
            private $guard;
            /**
             * @var Artifact
             */
            private $artifact;

            public function __construct(WorkflowRulesManagerLoopSafeGuard $guard, Artifact $artifact)
            {
                $this->guard    = $guard;
                $this->artifact = $artifact;
            }

            public function __invoke(): void
            {
                $this->nb_executions++;
                $this->guard->process($this->artifact, $this);
            }
        };

        $guard->process($artifact, $process);
        self::assertEquals(1, $process->nb_executions);
        self::assertTrue($logger->hasErrorRecords());
    }

    public function testAnArtifactCanBeProcessedSequentiallyMultipleTimes(): void
    {
        $logger          = new TestLogger();
        $workflow_logger = new WorkflowBackendLogger($logger, \Psr\Log\LogLevel::ERROR);

        $guard = new WorkflowRulesManagerLoopSafeGuard($workflow_logger);

        $artifact = ArtifactTestBuilder::anArtifact(123)->build();

        $process = new class {
            /**
             * @var int
             */
            public $nb_executions = 0;

            public function __invoke(): void
            {
                $this->nb_executions++;
            }
        };

        $guard->process($artifact, $process);
        $guard->process($artifact, $process);
        $guard->process($artifact, $process);
        self::assertEquals(3, $process->nb_executions);
        self::assertFalse($logger->hasErrorRecords());
    }
}
