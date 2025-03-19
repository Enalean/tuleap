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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PossibleParentsRetrieverTest extends TestCase
{
    private \Tracker $tracker;
    private \PFUser $user;
    private int $limit;
    private int $offset;
    private bool $can_create;
    private EventDispatcherStub $event_dispatcher;
    private \Tracker_ArtifactFactory&MockObject $artifact_factory;

    protected function setUp(): void
    {
        $this->tracker    = TrackerTestBuilder::aTracker()->build();
        $this->user       = UserTestBuilder::aUser()->build();
        $this->limit      = 0;
        $this->offset     = 0;
        $this->can_create = true;

        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
        $this->artifact_factory = $this->createMock(\Tracker_ArtifactFactory::class);
    }

    private function getParents(): PossibleParentSelector
    {
        $possible_parents_retriever = new PossibleParentsRetriever(
            $this->artifact_factory,
            $this->event_dispatcher,
        );

        return $possible_parents_retriever->getPossibleArtifactParents(
            $this->tracker,
            $this->user,
            $this->limit,
            $this->offset,
            $this->can_create
        );
    }

    public function testItReturnsWhateverPluginsSet(): void
    {
        $artifact_added_by_plugin = ArtifactTestBuilder::anArtifact(123)->build();
        $this->event_dispatcher   = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $event) use ($artifact_added_by_plugin) {
                $event->addPossibleParents(
                    new \Tracker_Artifact_PaginatedArtifacts([$artifact_added_by_plugin], 1)
                );
                return $event;
            }
        );

        $possible_parent_selector = $this->getParents();

        self::assertTrue($possible_parent_selector->isSelectorDisplayed());
        self::assertSame([$artifact_added_by_plugin], $possible_parent_selector->getPossibleParents()->getArtifacts());
    }

    public function testPluginsHaveThePaginationInfo(): void
    {
        $event = null;

        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $received) use (&$event): PossibleParentSelector {
                $event = $received;
                return $received;
            }
        );

        $this->limit  = 50;
        $this->offset = 100;
        $this->getParents();

        self::assertSame(50, $event->limit);
        self::assertSame(100, $event->offset);
    }

    public function testNoParentTrackerMeansNoNeedToDisplayTheSelector(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withParent(null)->build();

        $possible_parent_selector = $this->getParents();

        self::assertFalse($possible_parent_selector->isSelectorDisplayed());
    }

    public function testItDisablesCreateWhenToldTo(): void
    {
        $this->artifact_factory->method('getPaginatedPossibleParentArtifactsUserCanView')
            ->willReturn(new \Tracker_Artifact_PaginatedArtifacts([], 0));

        $parent_tracker   = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withId(567)
            ->build();
        $this->tracker    = TrackerTestBuilder::aTracker()->withParent($parent_tracker)->build();
        $this->can_create = false;

        $possible_parent_selector = $this->getParents();

        self::assertFalse($possible_parent_selector->canCreate());
    }

    public function testDisplayPossibleParents(): void
    {
        $artifact_from_hierarchy = ArtifactTestBuilder::anArtifact(123)->build();
        $this->artifact_factory->method('getPaginatedPossibleParentArtifactsUserCanView')
            ->willReturn(new \Tracker_Artifact_PaginatedArtifacts([$artifact_from_hierarchy], 1));

        $parent_tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withName('Epics')
            ->withShortName('epic')
            ->withId(567)
            ->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->withParent($parent_tracker)->build();

        $possible_parent_selector = $this->getParents();

        self::assertTrue($possible_parent_selector->isSelectorDisplayed());
        self::assertSame([$artifact_from_hierarchy], $possible_parent_selector->getPossibleParents()->getArtifacts());
        self::assertSame('epic', $possible_parent_selector->getParentLabel());
    }

    public function testDisplayPossibleParentsFromEventAndHierarchy(): void
    {
        $artifact_added_by_plugin = ArtifactTestBuilder::anArtifact(456)->build();
        $this->event_dispatcher   = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $event) use ($artifact_added_by_plugin) {
                $event->addPossibleParents(
                    new \Tracker_Artifact_PaginatedArtifacts([$artifact_added_by_plugin], 1)
                );
                return $event;
            }
        );

        $artifact_from_hierarchy = ArtifactTestBuilder::anArtifact(123)->build();
        $this->artifact_factory->method('getPaginatedPossibleParentArtifactsUserCanView')
            ->willReturn(new \Tracker_Artifact_PaginatedArtifacts([$artifact_from_hierarchy], 1));

        $parent_tracker = TrackerTestBuilder::aTracker()
            ->withUserCanView(true)
            ->withName('Epics')
            ->withShortName('epic')
            ->withId(567)
            ->build();
        $this->tracker  = TrackerTestBuilder::aTracker()->withParent($parent_tracker)->build();

        $possible_parent_selector = $this->getParents();

        self::assertTrue($possible_parent_selector->isSelectorDisplayed());
        self::assertEquals(
            [$artifact_added_by_plugin, $artifact_from_hierarchy],
            $possible_parent_selector->getPossibleParents()->getArtifacts()
        );
        self::assertSame('epic', $possible_parent_selector->getParentLabel());
    }

    public function testDoesNotDisplaySelectorWhenPluginExplicitlyForbidIt(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            static function (PossibleParentSelector $event): PossibleParentSelector {
                $event->disableSelector();
                return $event;
            }
        );

        $possible_parent_selector = $this->getParents();

        self::assertFalse($possible_parent_selector->isSelectorDisplayed());
    }
}
