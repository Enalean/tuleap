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
use Tuleap\Option\Option;
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

        $list->addMilestone(Option::fromValue($release1));
        $list->addMilestone(Option::fromValue($release2));
        $list->addMilestone(Option::fromValue($release3));
        $list->addMilestone(Option::fromValue($release4));
        $list->addMilestone(Option::fromValue($release5));

        self::assertEquals(5, $list->getListSize());
        $list->addMilestone(Option::fromValue($release6));
        self::assertEquals(5, $list->getListSize());
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
        $list->addMilestone(Option::fromValue($release1));
        $list->addSubMilestoneIntoMilestone($artifact_release_1->getId(), Option::fromValue($sprint1));
        $list->addMilestone(Option::fromValue($release2));
        $list->addSubMilestoneIntoMilestone($artifact_release_2->getId(), Option::fromValue($sprint2));
        $list->addSubMilestoneIntoMilestone($artifact_release_2->getId(), Option::fromValue($sprint3));
        self::assertEquals(5, $list->getListSize());

        $sprint4 = new Planning_ArtifactMilestone(
            $project,
            $this->createMock(Planning::class),
            ArtifactTestBuilder::anArtifact(40)->build(),
        );
        $list->addSubMilestoneIntoMilestone($artifact_release_1->getId(), Option::fromValue($sprint4));
        self::assertEquals(5, $list->getListSize());
        self::assertEquals(1, $list->getMilestoneList()[1]->getMilestone()->getArtifactId());
        self::assertEquals(2, $list->getMilestoneList()[2]->getMilestone()->getArtifactId());
        self::assertCount(1, $list->getMilestoneList()[1]->getSubMilestoneList());
        self::assertCount(2, $list->getMilestoneList()[2]->getSubMilestoneList());
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
        $milestone_list->addMilestone(Option::fromValue($release1));

        self::assertTrue($milestone_list->containsMilestone($release1->getArtifactId()));
    }

    public function testItReturnsFalseWhenMilestoneIsNotFound(): void
    {
        $milestone_list = new PromotedMilestoneList();
        self::assertFalse($milestone_list->containsMilestone(22));
    }
}
