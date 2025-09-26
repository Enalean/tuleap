<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Backlog;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_Milestone_Backlog_BacklogItemCollection;
use AgileDashboard_Milestone_Backlog_DescendantItemsCollection;
use AgileDashboard_SequenceIdManager;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_VirtualTopMilestone;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AgileDashboard_SequenceIdManagerTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Planning_VirtualTopMilestone&MockObject $virtual_top_milestone;
    private int $artifact_id_1;
    private int $artifact_id_2;
    private int $artifact_id_3;
    private int $artifact_id_6;
    private Artifact $artifact_1;
    private Artifact $artifact_2;
    private Artifact $artifact_3;
    private Artifact $artifact_4;
    private Artifact $artifact_5;
    private Artifact $artifact_6;
    private AgileDashboard_SequenceIdManager $sequence_id_manager;
    private Planning_ArtifactMilestone&MockObject $milestone_1;
    private Planning_ArtifactMilestone&MockObject $milestone_2;
    private AgileDashboard_Milestone_Backlog_BacklogFactory&MockObject $backlog_factory;
    private AgileDashboard_Milestone_Backlog_Backlog&MockObject $backlog_1;
    private AgileDashboard_Milestone_Backlog_Backlog&MockObject $backlog_2;
    private PFUser $user;
    private BacklogItemCollectionFactory&MockObject $backlog_item_collection_factory;
    private BacklogItem $backlog_item_1;
    private BacklogItem $backlog_item_2;
    private BacklogItem $backlog_item_3;
    private AgileDashboard_Milestone_Backlog_BacklogItemCollection $items_collection;

    #[\Override]
    protected function setUp(): void
    {
        $milestone_1_id    = 132;
        $this->milestone_1 = $this->createMock(Planning_ArtifactMilestone::class);
        $this->milestone_1->method('getArtifactId')->willReturn($milestone_1_id);

        $milestone_2_id    = 853;
        $this->milestone_2 = $this->createMock(Planning_ArtifactMilestone::class);
        $this->milestone_2->method('getArtifactId')->willReturn($milestone_2_id);

        $this->virtual_top_milestone = $this->createMock(Planning_VirtualTopMilestone::class);
        $this->virtual_top_milestone->method('getArtifactId')->willReturn(null);

        $this->backlog_1       = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->backlog_2       = $this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $this->backlog_factory = $this->createMock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);

        $this->backlog_item_collection_factory = $this->createMock(BacklogItemCollectionFactory::class);

        $this->sequence_id_manager = new AgileDashboard_SequenceIdManager(
            $this->backlog_factory,
            $this->backlog_item_collection_factory
        );
        $this->user                = new PFUser(['language_id' => 'en']);

        $this->artifact_id_1 = 123;
        $this->artifact_id_2 = 456;
        $this->artifact_id_3 = 789;
        $artifact_id_4       = 254;
        $artifact_id_5       = 255;
        $this->artifact_id_6 = 256;

        $this->artifact_1 = ArtifactTestBuilder::anArtifact($this->artifact_id_1)->build();
        $this->artifact_2 = ArtifactTestBuilder::anArtifact($this->artifact_id_2)->build();
        $this->artifact_3 = ArtifactTestBuilder::anArtifact($this->artifact_id_3)->build();
        $this->artifact_4 = ArtifactTestBuilder::anArtifact($artifact_id_4)->build();
        $this->artifact_5 = ArtifactTestBuilder::anArtifact($artifact_id_5)->build();
        $this->artifact_6 = ArtifactTestBuilder::anArtifact($this->artifact_id_6)->build();

        $this->backlog_item_1 = new BacklogItem($this->artifact_1, false);
        $this->backlog_item_2 = new BacklogItem($this->artifact_2, false);
        $this->backlog_item_3 = new BacklogItem($this->artifact_3, false);

        $this->items_collection = new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
    }

    public function testItReturnsNothingIfThereAreNoArtifactsInMilestonesBacklog(): void
    {
        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn(new AgileDashboard_Milestone_Backlog_DescendantItemsCollection());

        $this->backlog_factory->expects($this->once())->method('getBacklog')->with($this->user, $this->milestone_1)
            ->willReturn($this->backlog_1);

        self::assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, 2));
    }

    public function testItReturnsNothingIfTheArtifactIsNotInTheMilestoneBacklog(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->expects($this->once())->method('getBacklog')->with($this->user, $this->milestone_1)
            ->willReturn($this->backlog_1);
        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        self::assertNull($this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, 2));
    }

    public function testItReturns1IfTheArtifactIsInFirstPlace(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->expects($this->once())->method('getBacklog')->with($this->user, $this->milestone_1)
            ->willReturn($this->backlog_1);
        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        self::assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItReturns2IfTheArtifactIsInFirstPlace(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->expects($this->once())->method('getBacklog')->with($this->user, $this->milestone_1)
            ->willReturn($this->backlog_1);
        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        self::assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItKeepsInMemoryTheBacklogResult(): void
    {
        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_factory->expects($this->once())->method('getBacklog')->with($this->user, $this->milestone_1)
            ->willReturn($this->backlog_1);
        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        self::assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
        self::assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
    }

    public function testItCanDealWithMultipleCallWithDifferentMilestones(): void
    {
        $matcher = self::exactly(2);
        $this->backlog_factory->expects($matcher)->method('getBacklog')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($this->milestone_1, $parameters[1]);
                return $this->backlog_1;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($this->user, $parameters[0]);
                self::assertSame($this->milestone_2, $parameters[1]);
                return $this->backlog_2;
            }
        });

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_2);
        $backlog_items->push($this->artifact_1);
        $backlog_items->push($this->artifact_3);

        $this->backlog_1->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        $backlog_items = new AgileDashboard_Milestone_Backlog_DescendantItemsCollection();
        $backlog_items->push($this->artifact_4);
        $backlog_items->push($this->artifact_5);
        $backlog_items->push($this->artifact_6);

        $this->backlog_2->expects($this->once())->method('getArtifacts')->with($this->user)
            ->willReturn($backlog_items);

        self::assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_1)
        );
        self::assertEquals(
            3,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_2, $this->artifact_id_6)
        );
        self::assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->milestone_1, $this->artifact_id_2)
        );
    }

    public function testItCanDealWithTopBacklog(): void
    {
        $this->virtual_top_milestone->method('getArtifactId')->willReturn(2020);
        $this->items_collection->push($this->backlog_item_1);
        $this->items_collection->push($this->backlog_item_2);
        $this->items_collection->push($this->backlog_item_3);

        $this->backlog_item_collection_factory->expects($this->once())->method('getUnassignedOpenCollection')
            ->willReturn($this->items_collection);
        $this->backlog_factory->expects($this->once())->method('getSelfBacklog')
            ->willReturn($this->createMock(AgileDashboard_Milestone_Backlog_Backlog::class));

        self::assertEquals(
            1,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_1)
        );
        self::assertEquals(
            3,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_3)
        );
        self::assertEquals(
            2,
            $this->sequence_id_manager->getSequenceId($this->user, $this->virtual_top_milestone, $this->artifact_id_2)
        );
    }
}
