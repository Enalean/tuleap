<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Adapter\Events\CanSubmitNewArtifactEventProxy;
use Tuleap\ProgramManagement\Adapter\ProjectReferenceRetriever;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewArtifactHandlerTest extends TestCase
{
    private CanSubmitNewArtifactHandler $handler;
    private CanSubmitNewArtifact $event;
    private CanSubmitNewArtifactEventProxy $proxy;

    protected function setUp(): void
    {
        $program_increment_creator_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $iteration_creator_checker         = $this->createStub(IterationCreatorChecker::class);
        $program_builder                   = BuildProgramStub::stubValidProgram();
        $user                              = UserTestBuilder::aUser()->build();
        $project                           = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker                           = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject($project)
            ->build();

        $retrieve_full_project = RetrieveFullProjectStub::withProject($project);
        $program_increment_creator_checker->method('canCreateAProgramIncrement');
        $iteration_creator_checker->method('canCreateAnIteration');

        $this->handler = new CanSubmitNewArtifactHandler(
            new ConfigurationErrorsGatherer(
                $program_builder,
                $program_increment_creator_checker,
                $iteration_creator_checker,
                SearchTeamsOfProgramStub::buildTeams(104),
                new ProjectReferenceRetriever($retrieve_full_project),
            )
        );

        $this->event = new CanSubmitNewArtifact($user, $tracker);
        $this->proxy = CanSubmitNewArtifactEventProxy::buildFromEvent($this->event);
    }

    public function testItDisableArtifactSubmissionWhenCollectorFoundErrors(): void
    {
        $error_collector = new ConfigurationErrorsCollector(true);
        $error_collector->addWorkflowDependencyError(TrackerReferenceStub::withDefaults(), ProjectReferenceStub::buildGeneric());


        $this->handler->handle($this->proxy, $error_collector);
        self::assertFalse($this->event->canSubmitNewArtifact());
    }

    public function testKeepsSubmissionEnabled(): void
    {
        $error_collector = new ConfigurationErrorsCollector(true);

        $this->handler->handle($this->proxy, $error_collector);
        self::assertTrue($this->event->canSubmitNewArtifact());
    }
}
