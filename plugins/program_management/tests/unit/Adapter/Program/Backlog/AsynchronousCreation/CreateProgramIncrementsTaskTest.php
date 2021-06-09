<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ReplicationDataAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Feature\UserStoriesInMirroredProgramIncrementsPlanner;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Adapter\ProjectAdapter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingArtifactCreationStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementsCreator;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\ProgramStore;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CreateProgramIncrementsTaskTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|BuildFieldValues
     */
    private $changeset_values_adapter;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|ProgramStore
     */
    private $program_store;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\PlanningFactory
     */
    private $planning_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProgramIncrementsCreator
     */
    private $mirror_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PendingArtifactCreationStore
     */
    private $pending_artifact_creation_store;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserStoriesInMirroredProgramIncrementsPlanner
     */
    private $user_stories_planner;
    private TestLogger $logger;
    private CreateProgramIncrementsTask $task;

    protected function setUp(): void
    {
        $this->changeset_values_adapter        = $this->createStub(BuildFieldValues::class);
        $this->program_store                   = $this->createStub(ProgramStore::class);
        $this->project_manager                 = $this->createStub(\ProjectManager::class);
        $project_data_adapter                  = new ProjectAdapter($this->project_manager);
        $this->planning_factory                = $this->createStub(\PlanningFactory::class);
        $this->mirror_creator                  = $this->createMock(ProgramIncrementsCreator::class);
        $this->logger                          = new TestLogger();
        $this->pending_artifact_creation_store = $this->createMock(PendingArtifactCreationStore::class);
        $this->user_stories_planner            = $this->createMock(
            UserStoriesInMirroredProgramIncrementsPlanner::class
        );

        $this->task = new CreateProgramIncrementsTask(
            $this->changeset_values_adapter,
            new PlanningAdapter($this->planning_factory),
            $this->mirror_creator,
            $this->logger,
            $this->pending_artifact_creation_store,
            $this->user_stories_planner,
            $this->program_store,
            $project_data_adapter
        );
    }

    public function testItCreateMirrors(): void
    {
        $program_project  = ProjectTestBuilder::aProject()->withId(101)->build();
        $replication_data = $this->getReplicationData($program_project);

        $copied_values = $this->buildCopiedValues();
        $this->changeset_values_adapter->method('buildCollection')->willReturn($copied_values);

        $team_project_id = 102;
        $team_project    = ProjectTestBuilder::aProject()->withId($team_project_id)->build();
        $this->program_store->method('getTeamProjectIdsForGivenProgramProject')
            ->willReturn([['team_project_id' => $team_project_id]]);
        $this->project_manager->method('getProject')->willReturn($team_project);

        $planning = new \Planning(1, 'Root planning', $team_project_id, '', '');
        $planning->setPlanningTracker($replication_data->getTracker()->getFullTracker());
        $this->planning_factory->method('getRootPlanning')->willReturn($planning);

        $this->mirror_creator->expects(self::once())->method('createProgramIncrements');

        $this->pending_artifact_creation_store->expects(self::once())
            ->method('deleteArtifactFromPendingCreation')
            ->with($replication_data->getArtifact()->getId(), (int) $replication_data->getUser()->getId());

        $this->user_stories_planner->expects(self::once())->method('plan');

        $this->task->createProgramIncrements($replication_data);
    }

    public function testItLogsWhenAnExceptionOccurrs(): void
    {
        $program_project  = ProjectTestBuilder::aProject()->withId(101)->build();
        $replication_data = $this->getReplicationData($program_project);

        $this->changeset_values_adapter->method('buildCollection')
            ->willThrowException(new FieldRetrievalException(1, 'title'));

        $this->user_stories_planner->expects(self::never())->method('plan');

        $this->task->createProgramIncrements($replication_data);
        self::assertTrue($this->logger->hasErrorRecords());
    }

    private function buildCopiedValues(): SourceChangesetValuesCollection
    {
        $planned_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);

        $title_value         = new TitleValue('Program Release');
        $description_value   = new DescriptionValue('Description', 'text');
        $status_value        = new StatusValue([$planned_value]);
        $start_date_value    = new StartDateValue("2020-10-01");
        $end_period_value    = new EndPeriodValue("2020-10-30");
        $artifact_link_value = new ArtifactLinkValue(112);
        $submission_date     = new SubmissionDate(123456789);

        return new SourceChangesetValuesCollection(
            112,
            $title_value,
            $description_value,
            $status_value,
            $submission_date,
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }

    private function getReplicationData(\Project $program_project): ReplicationData
    {
        $user      = UserTestBuilder::aUser()->withId(1001)->build();
        $tracker   = TrackerTestBuilder::aTracker()->withId(89)->withProject($program_project)->build();
        $artifact  = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->build();
        $changeset = new \Tracker_Artifact_Changeset(1, $artifact, $user->getId(), 1234567890, null);

        return ReplicationDataAdapter::build($artifact, $user, $changeset);
    }
}
