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
use Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar\PromotedMilestoneWithItsSubmilestones;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class PromotedMilestoneListTest extends TestCase
{
    public function testItAddsReleaseUntilMaxListSizeIsReached(): void
    {
        $project  = ProjectTestBuilder::aProject()->withId(101)->build();
        $list     = new PromotedMilestoneList();
        $release1 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(1)->build(),
        );
        $release2 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(2)->build(),
        );
        $release3 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(3)->build(),
        );
        $release4 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(4)->build(),
        );
        $release5 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(5)->build(),
        );
        $release6 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(6)->build(),
        );

        self::assertCount(0, $list->getMilestoneList());
        self::assertFalse($list->isListSizeLimitReached());

        $list->addMilestone($release1);
        self::assertTrue($list->containsMilestone($release1->getArtifactId()));
        self::assertFalse($list->isListSizeLimitReached());
        self::assertSame(1, $this->getListSize($list));

        $list->addMilestone($release2);
        self::assertTrue($list->containsMilestone($release2->getArtifactId()));
        self::assertFalse($list->isListSizeLimitReached());
        self::assertSame(2, $this->getListSize($list));

        $list->addMilestone($release3);
        self::assertTrue($list->containsMilestone($release3->getArtifactId()));
        self::assertFalse($list->isListSizeLimitReached());
        self::assertSame(3, $this->getListSize($list));

        $list->addMilestone($release4);
        self::assertTrue($list->containsMilestone($release4->getArtifactId()));
        self::assertFalse($list->isListSizeLimitReached());
        self::assertSame(4, $this->getListSize($list));

        $list->addMilestone($release5);
        self::assertTrue($list->containsMilestone($release5->getArtifactId()));
        self::assertTrue($list->isListSizeLimitReached());
        self::assertSame(5, $this->getListSize($list));

        $list->addMilestone($release6);
        self::assertFalse($list->containsMilestone($release6->getArtifactId()));
        self::assertTrue($list->isListSizeLimitReached());
        self::assertSame(5, $this->getListSize($list));
    }

    private function getListSize(PromotedMilestoneList $list): int
    {
        return array_reduce(
            $list->getMilestoneList(),
            function (int $sum, PromotedMilestoneWithItsSubmilestones $milestone_with_its_submilestones) {
                return $sum + 1 + count($milestone_with_its_submilestones->getSubMilestoneList());
            },
            0
        );
    }

    public function testItAddsSprintsUntilMaxListSizeIsReached(): void
    {
        $list               = new PromotedMilestoneList();
        $project            = ProjectTestBuilder::aProject()->withId(101)->build();
        $artifact_release_1 = ArtifactTestBuilder::anArtifact(1)->build();
        $release1           = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            $artifact_release_1,
        );
        $artifact_release_2 = ArtifactTestBuilder::anArtifact(2)->build();
        $release2           = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            $artifact_release_2,
        );

        $sprint1 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(10)->build(),
        );
        $sprint2 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(20)->build(),
        );
        $sprint3 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(30)->build(),
        );
        $list->addMilestone($release1);
        $list->addSubMilestone($release1, $sprint1);
        $list->addMilestone($release2);
        $list->addSubMilestone($release2, $sprint2);
        $list->addSubMilestone($release2, $sprint3);
        self::assertSame(5, $this->getListSize($list));

        $sprint4 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(40)->build(),
        );
        $list->addSubMilestone($release1, $sprint4);
        self::assertSame(5, $this->getListSize($list));
        self::assertSame(1, $list->getMilestoneList()[0]->getMilestone()->getArtifactId());
        self::assertSame(2, $list->getMilestoneList()[1]->getMilestone()->getArtifactId());
        self::assertCount(1, $list->getMilestoneList()[0]->getSubMilestoneList());
        self::assertCount(2, $list->getMilestoneList()[1]->getSubMilestoneList());
    }

    public function testItReturnsTrueWhenMilestoneExists(): void
    {
        $milestone_list     = new PromotedMilestoneList();
        $project            = ProjectTestBuilder::aProject()->withId(101)->build();
        $artifact_release_1 = ArtifactTestBuilder::anArtifact(1)->build();
        $release1           = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            $artifact_release_1,
        );
        $milestone_list->addMilestone($release1);

        self::assertTrue($milestone_list->containsMilestone($release1->getArtifactId()));
    }

    public function testItReturnsFalseWhenMilestoneIsNotFound(): void
    {
        $milestone_list = new PromotedMilestoneList();
        self::assertFalse($milestone_list->containsMilestone(22));
    }
}
