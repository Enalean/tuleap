<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\ContributorMilestoneTrackerCollection;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFields;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldsGatherer;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\TimeframeFields;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Changeset\Validation\ChangesetValidationContext;

final class MirrorMilestonesCreatorTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var MirrorMilestonesCreator
     */
    private $mirrors_creator;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SynchronizedFieldsGatherer
     */
    private $target_fields_gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_ArtifactCreator
     */
    private $artifact_creator;

    protected function setUp(): void
    {
        $this->transaction_executor   = new DBTransactionExecutorPassthrough();
        $this->target_fields_gatherer = M::mock(SynchronizedFieldsGatherer::class);
        $this->artifact_creator       = M::mock(\Tracker_ArtifactCreator::class);
        $this->mirrors_creator        = new MirrorMilestonesCreator(
            $this->transaction_executor,
            $this->target_fields_gatherer,
            $this->artifact_creator
        );
    }

    public function testItCreatesMirrorMilestones(): void
    {
        $copied_values              = $this->buildCopiedValues();
        $first_contributor_project  = new \Project(['group_id' => '102']);
        $first_tracker              = $this->buildTestTracker(8, $first_contributor_project);
        $second_contributor_project = new \Project(['group_id' => '103']);
        $second_tracker             = $this->buildTestTracker(9, $second_contributor_project);
        $trackers                   = new ContributorMilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $current_user               = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($first_tracker)
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($second_tracker)
            ->andReturn($this->buildSynchronizedFields(2001, 2002, 2003, 2004, 2005, 2006));
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($first_tracker, M::any(), $current_user, 123456789, false, false, M::type(ChangesetValidationContext::class))
            ->andReturnTrue();
        $this->artifact_creator->shouldReceive('create')
            ->once()
            ->with($second_tracker, M::any(), $current_user, 123456789, false, false, M::type(ChangesetValidationContext::class))
            ->andReturnTrue();

        $this->mirrors_creator->createMirrors($copied_values, $trackers, $current_user);
    }

    public function testItThrowsWhenThereIsAnErrorDuringCreation(): void
    {
        $copied_values         = $this->buildCopiedValues();
        $a_contributor_project = new \Project(['group_id' => '110']);
        $tracker               = $this->buildTestTracker(10, $a_contributor_project);
        $trackers              = new ContributorMilestoneTrackerCollection([$tracker]);
        $current_user          = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')
            ->andReturn($this->buildSynchronizedFields(1001, 1002, 1003, 1004, 1005, 1006));
        $this->artifact_creator->shouldReceive('create')->andReturnFalse();

        $this->expectException(MirrorMilestoneCreationException::class);
        $this->mirrors_creator->createMirrors($copied_values, $trackers, $current_user);
    }

    private function buildCopiedValues(): CopiedValues
    {
        $project = \Project::buildForTest();
        $tracker = $this->buildTestTracker(89, $project);
        $title_field = M::mock(\Tracker_FormElement_Field::class);
        $title_field->shouldReceive('getTracker')->andReturn($tracker);
        $title_changeset_value = new \Tracker_Artifact_ChangesetValue_String(10000, M::mock(\Tracker_Artifact_Changeset::class), $title_field, true, 'Aggregator Release', 'text');

        $description_field = M::mock(\Tracker_FormElement_Field::class);
        $description_field->shouldReceive('getTracker')->andReturn($tracker);
        $description_changeset_value = new \Tracker_Artifact_ChangesetValue_Text(10001, M::mock(\Tracker_Artifact_Changeset::class), $description_field, true, 'Description', 'text');

        return new CopiedValues($title_changeset_value, $description_changeset_value, 123456789, 112);
    }

    private function buildTestTracker(int $tracker_id, \Project $project): \Tracker
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
        return $tracker;
    }

    private function buildSynchronizedFields(
        int $artifact_link_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_date_id
    ): SynchronizedFields {
        return new SynchronizedFields(
            new \Tracker_FormElement_Field_ArtifactLink($artifact_link_id, 89, 1000, 'art_link', 'Links', 'Irrelevant', true, 'P', false, '', 1),
            new \Tracker_FormElement_Field_String($title_id, 89, 1000, 'title', 'Title', 'Irrelevant', true, 'P', true, '', 2),
            new \Tracker_FormElement_Field_Text($description_id, 89, 1000, 'description', 'Description', 'Irrelevant', true, 'P', false, '', 3),
            new \Tracker_FormElement_Field_Selectbox($status_id, 89, 1000, 'status', 'Status', 'Irrelevant', true, 'P', false, '', 4),
            TimeframeFields::fromStartAndEndDates(
                $this->buildTestDateField($start_date_id, 89),
                $this->buildTestDateField($end_date_id, 89)
            )
        );
    }

    private function buildTestDateField(int $id, int $tracker_id): \Tracker_FormElement_Field_Date
    {
        return new \Tracker_FormElement_Field_Date($id, $tracker_id, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 1);
    }
}
