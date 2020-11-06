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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ScaledAgile\Adapter\Program\ArtifactCreationException;
use Tuleap\ScaledAgile\Adapter\Program\ArtifactCreatorAdapter;
use Tuleap\ScaledAgile\Adapter\Program\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ArtifactData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class ProgramIncrementsCreatorTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProgramIncrementsCreator
     */
    private $mirrors_creator;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SynchronizedFieldsAdapter
     */
    private $synchronized_fields_adapter;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|StatusValueMapper
     */
    private $status_mapper;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ArtifactCreatorAdapter
     */
    private $artifact_creator;

    protected function setUp(): void
    {
        $transaction_executor              = new DBTransactionExecutorPassthrough();
        $this->synchronized_fields_adapter = M::mock(SynchronizedFieldsAdapter::class);
        $this->artifact_creator            = M::mock(ArtifactCreatorAdapter::class);
        $this->status_mapper               = M::mock(StatusValueMapper::class);
        $this->mirrors_creator             = new ProgramIncrementsCreator(
            $transaction_executor,
            $this->synchronized_fields_adapter,
            $this->status_mapper,
            $this->artifact_creator
        );
    }

    public function testItCreatesMirrorMilestones(): void
    {
        $copied_values       = $this->buildCopiedValues();
        $first_team_project  = new \Project(['group_id' => '102']);
        $test_tracker_data   = $this->buildTestTrackerData(8, $first_team_project);
        $second_team_project = new \Project(['group_id' => '103']);
        $second_tracker_data = $this->buildTestTrackerData(9, $second_team_project);
        $trackers            = new ProgramIncrementsTrackerCollection([$test_tracker_data, $second_tracker_data]);
        $current_user        = UserTestBuilder::aUser()->build();

        $this->synchronized_fields_adapter->shouldReceive('build')
            ->with($test_tracker_data)
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->synchronized_fields_adapter->shouldReceive('build')
            ->with($second_tracker_data)
            ->andReturn($this->buildSynchronizedFields(2001, 2002, 2003, 2004, 2005, 2006));
        $this->status_mapper->shouldReceive('mapStatusValueByDuckTyping')
            ->andReturns($this->buildMappedValue(5000), $this->buildMappedValue(6000));
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($test_tracker_data, M::any(), $current_user, $copied_values->getSubmittedOn())
            ->andReturn(new ArtifactData(201, 123456789));
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($second_tracker_data, M::any(), $current_user, $copied_values->getSubmittedOn())
            ->andReturn(new ArtifactData(202, 123456789));

        $this->mirrors_creator->createProgramIncrements($copied_values, $trackers, $current_user);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $copied_values         = $this->buildCopiedValues();
        $a_team_project = new \Project(['group_id' => '110']);
        $tracker               = $this->buildTestTrackerData(10, $a_team_project);
        $trackers              = new ProgramIncrementsTrackerCollection([$tracker]);
        $current_user          = UserTestBuilder::aUser()->build();

        $this->synchronized_fields_adapter->shouldReceive('build')
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->status_mapper->shouldReceive('mapStatusValueByDuckTyping')
            ->andReturn($this->buildMappedValue(5000));
        $this->artifact_creator->shouldReceive('create')->andThrow(new ArtifactCreationException());

        $this->expectException(ProgramIncrementArtifactCreationException::class);
        $this->mirrors_creator->createProgramIncrements($copied_values, $trackers, $current_user);
    }

    private function buildCopiedValues(): SourceChangesetValuesCollection
    {
        $planned_value          = new \Tracker_FormElement_Field_List_Bind_StaticValue(2000, 'Planned', 'Irrelevant', 1, false);

        $title_value = new TitleValue('Program Release');
        $description_value = new DescriptionValue('Description', 'text');
        $status_value        = new StatusValue([$planned_value]);
        $start_date_value    = new StartDateValue("2020-10-01");
        $end_period_value    = new EndPeriodValue("2020-10-31");
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

    private function buildTestTrackerData(int $tracker_id, \Project $project): TrackerData
    {
        $tracker = new \Tracker(
            $tracker_id,
            $project->getID(),
            'Irrelevant',
            'Irrelevant',
            'irrelevant',
            false,
            null,
            null,
            null,
            null,
            true,
            false,
            \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
            \Tuleap\Tracker\TrackerColor::default(),
            false
        );
        $tracker->setProject($project);
        return TrackerDataAdapter::build($tracker);
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        $artifact_link_field_data = new FieldData(new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1));

        $title_field_data = new FieldData(new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2));

        $description_field_data = new FieldData(new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3));

        $status_field_data = new FieldData(new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4));

        $start_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date($start_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5));

        $end_date_field_data = new FieldData(new \Tracker_FormElement_Field_Date($end_date_id, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6));

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }

    private function buildMappedValue(int $bind_value_id): MappedStatusValue
    {
        return new MappedStatusValue([$bind_value_id]);
    }
}
