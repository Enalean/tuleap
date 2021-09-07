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

use ProjectManager;
use Tuleap\ProgramManagement\Adapter\ProgramManagementProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Tests\Builder\ProjectReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TrackerReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CanSubmitNewArtifactHandlerTest extends TestCase
{
    private \PFUser $user;
    private \Tracker $tracker;
    private CanSubmitNewArtifactHandler $handler;

    protected function setUp(): void
    {
        $program_increment_creator_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $iteration_creator_checker         = $this->createStub(IterationCreatorChecker::class);
        $project_manager                   = $this->createMock(ProjectManager::class);
        $program_builder                   = BuildProgramStub::stubValidProgram();
        $this->user                        = UserTestBuilder::aUser()->build();
        $project                           = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->tracker                     = TrackerTestBuilder::aTracker()->withId(98)
            ->withProject($project)
            ->build();

        $project_manager->method('getProject')->willReturn($project);
        $program_increment_creator_checker->method('canCreateAProgramIncrement');
        $iteration_creator_checker->method('canCreateAnIteration');

        $this->handler = new CanSubmitNewArtifactHandler(
            new ConfigurationErrorsGatherer(
                $program_builder,
                $program_increment_creator_checker,
                $iteration_creator_checker,
                SearchTeamsOfProgramStub::buildTeams(104),
                new ProgramManagementProjectAdapter($project_manager),
                RetrieveUserStub::withUser($this->user)
            )
        );
    }

    public function testItDisableArtifactSubmissionWhenCollectorFoundErrors(): void
    {
        $error_collector = new ConfigurationErrorsCollector(true);
        $error_collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        $event = new CanSubmitNewArtifact($this->user, $this->tracker);

        $this->handler->handle($event, $error_collector, UserIdentifierStub::buildGenericUser());
        self::assertFalse($event->canSubmitNewArtifact());
    }

    public function testKeepsSubmissionEnabled(): void
    {
        $error_collector = new ConfigurationErrorsCollector(true);
        $event           = new CanSubmitNewArtifact($this->user, $this->tracker);

        $this->handler->handle($event, $error_collector, UserIdentifierStub::buildGenericUser());
        self::assertTrue($event->canSubmitNewArtifact());
    }
}
