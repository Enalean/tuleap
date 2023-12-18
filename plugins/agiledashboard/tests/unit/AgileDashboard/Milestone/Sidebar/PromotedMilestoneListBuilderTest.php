<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Sidebar;

use Planning;
use Planning_ArtifactMilestone;
use Planning_VirtualTopMilestone;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Stub\Milestone\RetrieveMilestonesWithSubMilestonesStub;
use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\PromotedMilestoneBuilderStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

/**
 * @psalm-immutable
 */
final class PromotedMilestoneListBuilderTest extends TestCase
{
    public function testItBuildsAPromotedArtifactAndSubArtifactUntilLimitIsReached(): void
    {
        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $user     = UserTestBuilder::buildWithDefaults();
        $project  = ProjectTestBuilder::aProject()->build();
        $planning = $this->createMock(Planning::class);
        $tracker  = TrackerTestBuilder::aTracker()->withId(3)->build();
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $planning->method('getId')->willReturn(1);
        $sub_tracker = TrackerTestBuilder::aTracker()->withId(4)->build();

        $top_milestone = new Planning_VirtualTopMilestone($project, $planning);

        $artifact               = ArtifactTestBuilder::anArtifact(50)->withTitle('Title')->build();
        $sub_artifact1          = ArtifactTestBuilder::anArtifact(60)->withTitle('Sub Title 1')->build();
        $sub_artifact2          = ArtifactTestBuilder::anArtifact(70)->withTitle('Sub Title 2')->build();
        $sub_artifact3          = ArtifactTestBuilder::anArtifact(80)->withTitle('Sub Title 3')->build();
        $sub_artifact4          = ArtifactTestBuilder::anArtifact(90)->withTitle('Sub Title 4')->build();
        $sub_artifact5          = ArtifactTestBuilder::anArtifact(100)->withTitle('Sub Title 5')->build();
        $promoted_release       = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $artifact,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact1 = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact1,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact2 = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact2,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact3 = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact3,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact4 = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact4,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );
        $promoted_sub_artifact5 = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $sub_artifact5,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );

        $artifact_factory
            ->method('getInstanceFromRow')
            ->willReturnOnConsecutiveCalls(
                $artifact,
                $sub_artifact1,
                $sub_artifact2,
                $sub_artifact3,
                $sub_artifact4,
                $sub_artifact5
            );


        $rows = [
            $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact1->getId()),
            $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact2->getId()),
            $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact3->getId()),
            $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact4->getId()),
            $this->aMilestoneArrayWithASubmilestone($tracker->getId(), $artifact->getId(), $sub_tracker->getId(), $sub_artifact5->getId()),
        ];

        $list_builder = new PromotedMilestoneListBuilder(
            $artifact_factory,
            PromotedMilestoneBuilderStub::buildWithPlanningArtifactMilestone(
                $promoted_release,
                $promoted_sub_artifact1,
                $promoted_sub_artifact2,
                $promoted_sub_artifact3,
                $promoted_sub_artifact4,
                $promoted_sub_artifact5
            ),
            RetrieveMilestonesWithSubMilestonesStub::withMilestones($rows)
        );

        $items = $list_builder->buildPromotedMilestoneList($user, $top_milestone);
        self::assertCount(1, $items->getMilestoneList());
        self::assertSame(5, $items->getListSize());
    }

    public function testPromotedReleasesAreCached(): void
    {
        $artifact_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $user     = UserTestBuilder::buildWithDefaults();
        $project  = ProjectTestBuilder::aProject()->build();
        $planning = $this->createMock(Planning::class);
        $tracker  = TrackerTestBuilder::aTracker()->withId(3)->build();
        $planning->method('getPlanningTrackerId')->willReturn($tracker->getId());
        $planning->method('getId')->willReturn(1);
        $top_milestone = new Planning_VirtualTopMilestone($project, $planning);

        $artifact         = ArtifactTestBuilder::anArtifact(5)->withTitle('Title')->build();
        $promoted_release = new Planning_ArtifactMilestone(
            $project,
            $planning,
            $artifact,
            $this->createMock(ScrumForMonoMilestoneChecker::class)
        );

        $artifact_factory
            ->method('getInstanceFromRow')
            ->willReturnOnConsecutiveCalls($artifact, $artifact, $artifact);


        $list_builder = new PromotedMilestoneListBuilder(
            $artifact_factory,
            PromotedMilestoneBuilderStub::buildWithPlanningArtifactMilestone(
                $promoted_release,
                $promoted_release,
                $promoted_release
            ),
            RetrieveMilestonesWithSubMilestonesStub::withMilestones([
                $this->aMilestoneArrayWithoutASubmilestone($tracker->getId(), $artifact->getId()),
            ])
        );

        $items = $list_builder->buildPromotedMilestoneList($user, $top_milestone);
        self::assertCount(1, $items->getMilestoneList());
        self::assertSame(1, $items->getListSize());
    }

    private function aMilestoneArrayWithASubmilestone(int $tracker_id, int $artifact_id, int $sub_tracker_id, int $sub_artifact_id): array
    {
        return [
            'parent_id'                             => "$artifact_id",
            'parent_tracker'                        => "$tracker_id",
            'parent_changeset'                      => '501',
            'parent_submitted_by'                   => '101',
            'parent_submitted_on'                   => '1234567890',
            'parent_use_artifact_permissions'       => '1',
            'parent_per_tracker_artifact_id'        => '1',
            'submilestone_id'                       => $sub_artifact_id,
            'submilestone_tracker'                  => "$sub_tracker_id",
            'submilestone_changeset'                => '502',
            'submilestone_submitted_by'             => '101',
            'submilestone_submitted_on'             => '1324567980',
            'submilestone_use_artifact_permissions' => '1',
            'submilestone_per_tracker_artifact_id'  => '1',
        ];
    }

    private function aMilestoneArrayWithoutASubmilestone(int $tracker_id, int $artifact_id): array
    {
        return [
            'parent_id'                             => "$artifact_id",
            'parent_tracker'                        => "$tracker_id",
            'parent_changeset'                      => '501',
            'parent_submitted_by'                   => '101',
            'parent_submitted_on'                   => '1234567890',
            'parent_use_artifact_permissions'       => '1',
            'parent_per_tracker_artifact_id'        => '1',
            'submilestone_id'                       => null,
            'submilestone_tracker'                  => null,
            'submilestone_changeset'                => null,
            'submilestone_submitted_by'             => null,
            'submilestone_submitted_on'             => null,
            'submilestone_use_artifact_permissions' => null,
            'submilestone_per_tracker_artifact_id'  => null,
        ];
    }
}
