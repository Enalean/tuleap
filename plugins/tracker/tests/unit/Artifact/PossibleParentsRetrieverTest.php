<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PossibleParentsRetrieverTest extends TestCase
{
    private \Tracker $tracker;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->build();
        $this->user    = UserTestBuilder::aUser()->build();
    }

    public function testItReturnsWhateverPluginsSet(): void
    {
        $event_manager              = new class implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                assert($event instanceof PossibleParentSelector);
                $event->addPossibleParents(new \Tracker_Artifact_PaginatedArtifacts(
                    [
                        ArtifactTestBuilder::anArtifact(123)->build(),
                    ],
                    1
                ));
                return $event;
            }
        };
        $possible_parents_retriever = new PossibleParentsRetriever(
            $this->createStub(\Tracker_ArtifactFactory::class),
            $event_manager,
        );

        $this->tracker->setParent(null);

        $possible_parent_selector = $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 0, 0, true);

        assertTrue($possible_parent_selector->isSelectorDisplayed());
        assertEquals([ArtifactTestBuilder::anArtifact(123)->build()], $possible_parent_selector->getPossibleParents()->getArtifacts());
    }

    public function testPluginsHaveThePaginationInfo(): void
    {
        $event         = null;
        $event_manager = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $received) use (&$event): PossibleParentSelector {
                $event = $received;
                return $received;
            }
        );

        $this->tracker->setParent();

        $possible_parents_retriever = new PossibleParentsRetriever(
            $this->createStub(\Tracker_ArtifactFactory::class),
            $event_manager,
        );

        $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 50, 100, true);

        assertEquals(50, $event->limit);
        assertEquals(100, $event->offset);
    }

    public function testNoParentTrackerMeansNoNeedToDisplayTheSelector(): void
    {
        $possible_parents_retriever = new PossibleParentsRetriever(
            $this->createStub(\Tracker_ArtifactFactory::class),
            EventDispatcherStub::withCallback(
                static function (PossibleParentSelector $event): PossibleParentSelector {
                    return $event;
                }
            ),
        );

        $this->tracker->setParent();

        $possible_parent_selector = $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 0, 0, true);

        assertFalse($possible_parent_selector->isSelectorDisplayed());
    }

    public function testDisplayPossibleParents(): void
    {
        $artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
        $artifact_factory
            ->method('getPaginatedPossibleParentArtifactsUserCanView')
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts(
                    [
                        ArtifactTestBuilder::anArtifact(123)->build(),
                    ],
                    1
                )
            );

        $parent_tracker = $this->createStub(\Tracker::class);
        $parent_tracker->method('userCanView')->willReturn(true);
        $parent_tracker->method('isDeleted')->willReturn(false);
        $parent_tracker->method('getName')->willReturn('Epics');
        $parent_tracker->method('getItemName')->willReturn('epic');
        $parent_tracker->method('getId')->willReturn(567);
        $this->tracker->setParent($parent_tracker);

        $possible_parents_retriever = new PossibleParentsRetriever(
            $artifact_factory,
            new \EventManager(),
        );

        $possible_parent_selector = $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 0, 0, true);

        assertTrue($possible_parent_selector->isSelectorDisplayed());
        assertEquals([ArtifactTestBuilder::anArtifact(123)->build()], $possible_parent_selector->getPossibleParents()->getArtifacts());
        assertEquals($possible_parent_selector->getParentLabel(), 'epic');
    }

    public function testDisplayPossibleParentsFromEventAndHierarchy(): void
    {
        $event_manager = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $event): PossibleParentSelector {
                $event->addPossibleParents(new \Tracker_Artifact_PaginatedArtifacts(
                    [
                        ArtifactTestBuilder::anArtifact(124)->build(),
                    ],
                    1
                ));
                return $event;
            }
        );

        $artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
        $artifact_factory
            ->method('getPaginatedPossibleParentArtifactsUserCanView')
            ->willReturn(
                new \Tracker_Artifact_PaginatedArtifacts(
                    [
                        ArtifactTestBuilder::anArtifact(123)->build(),
                    ],
                    1
                )
            );

        $parent_tracker = $this->createStub(\Tracker::class);
        $parent_tracker->method('userCanView')->willReturn(true);
        $parent_tracker->method('isDeleted')->willReturn(false);
        $parent_tracker->method('getName')->willReturn('Epics');
        $parent_tracker->method('getItemName')->willReturn('epic');
        $parent_tracker->method('getId')->willReturn(567);
        $this->tracker->setParent($parent_tracker);

        $possible_parents_retriever = new PossibleParentsRetriever(
            $artifact_factory,
            $event_manager,
        );

        $possible_parent_selector = $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 0, 0, true);

        assertTrue($possible_parent_selector->isSelectorDisplayed());
        assertEquals([ArtifactTestBuilder::anArtifact(124)->build(), ArtifactTestBuilder::anArtifact(123)->build()], $possible_parent_selector->getPossibleParents()->getArtifacts());
        assertEquals($possible_parent_selector->getParentLabel(), 'epic');
    }

    public function testDoesNotDisplaySelectorWhenPluginExplicitlyForbidIt(): void
    {
        $event_manager = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $event): PossibleParentSelector {
                $event->disableSelector();
                return $event;
            }
        );

        $artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);


        $possible_parents_retriever = new PossibleParentsRetriever(
            $artifact_factory,
            $event_manager,
        );

        $possible_parent_selector = $possible_parents_retriever->getPossibleArtifactParents($this->tracker, $this->user, 0, 0, true);

        assertFalse($possible_parent_selector->isSelectorDisplayed());
    }
}
