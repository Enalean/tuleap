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
     * @var M\LegacyMockInterface|M\MockInterface|TargetFieldsGatherer
     */
    private $target_fields_gatherer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_ArtifactCreator
     */
    private $artifact_creator;

    protected function setUp(): void
    {
        $this->transaction_executor   = new DBTransactionExecutorPassthrough();
        $this->target_fields_gatherer = M::mock(TargetFieldsGatherer::class);
        $this->artifact_creator       = M::mock(\Tracker_ArtifactCreator::class);
        $this->mirrors_creator        = new MirrorMilestonesCreator(
            $this->transaction_executor,
            $this->target_fields_gatherer,
            $this->artifact_creator
        );
    }

    public function testItCreatesMirrorMilestones(): void
    {
        $copied_values  = new CopiedValues($this->mockTitleChangesetValue(), 123456789, 110);
        $first_tracker  = $this->buildTestTracker(8);
        $second_tracker = $this->buildTestTracker(9);
        $trackers       = new ContributorMilestoneTrackerCollection([$first_tracker, $second_tracker]);
        $current_user   = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($first_tracker)
            ->andReturn($this->buildTargetFields(1001, 1002));
        $this->target_fields_gatherer->shouldReceive('gather')
            ->with($second_tracker)
            ->andReturn($this->buildTargetFields(1003, 1004));
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
        $copied_values = new CopiedValues($this->mockTitleChangesetValue(), 123456789, 110);
        $tracker       = $this->buildTestTracker(10);
        $trackers      = new ContributorMilestoneTrackerCollection([$tracker]);
        $current_user  = UserTestBuilder::aUser()->build();

        $this->target_fields_gatherer->shouldReceive('gather')->andReturn($this->buildTargetFields(1001, 1002));
        $this->artifact_creator->shouldReceive('create')->andReturnFalse();

        $this->expectException(MirrorMilestoneCreationException::class);
        $this->mirrors_creator->createMirrors($copied_values, $trackers, $current_user);
    }

    private function buildTestTracker(int $tracker_id): \Tracker
    {
        return new \Tracker(
            $tracker_id,
            null,
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
    }

    private function mockTitleChangesetValue(): \Tracker_Artifact_ChangesetValue_String
    {
        $project = \Project::buildForTest();
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn($project);
        $field = M::mock(\Tracker_FormElement_Field::class);
        $field->shouldReceive('getTracker')->andReturn($tracker);
        return new \Tracker_Artifact_ChangesetValue_String(
            122,
            M::mock(\Tracker_Artifact_Changeset::class),
            $field,
            true,
            'Aggregator Release',
            'text'
        );
    }

    private function buildTargetFields(int $artifact_link_field_id, int $title_field_id): TargetFields
    {
        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $artifact_link_field->shouldReceive('getId')->andReturn((string) $artifact_link_field_id);
        $title_field = M::mock(\Tracker_FormElement_Field_Text::class);
        $title_field->shouldReceive('getId')->andReturn((string) $title_field_id);
        return new TargetFields($artifact_link_field, $title_field);
    }
}
