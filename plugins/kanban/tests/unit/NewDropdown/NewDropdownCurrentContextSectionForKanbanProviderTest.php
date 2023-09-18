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

namespace Tuleap\Kanban\NewDropdown;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Kanban\Kanban;
use Tuleap\Kanban\KanbanActionsChecker;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\KanbanNotFoundException;
use Tuleap\Kanban\KanbanUserCantAddArtifactException;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

final class NewDropdownCurrentContextSectionForKanbanProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \Tuleap\Kanban\KanbanFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $kanban_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TrackerFactory
     */
    private $tracker_factory;
    private TrackerNewDropdownLinkPresenterBuilder $presenter_builder;
    /**
     * @var KanbanActionsChecker&\PHPUnit\Framework\MockObject\MockObject
     */
    private $kanban_actions_checker;
    private NewDropdownCurrentContextSectionForKanbanProvider $provider;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->kanban_factory         = $this->createMock(\Tuleap\Kanban\KanbanFactory::class);
        $this->tracker_factory        = $this->createMock(\TrackerFactory::class);
        $this->presenter_builder      = new TrackerNewDropdownLinkPresenterBuilder();
        $this->kanban_actions_checker = $this->createMock(KanbanActionsChecker::class);

        $this->provider = new NewDropdownCurrentContextSectionForKanbanProvider(
            $this->kanban_factory,
            $this->tracker_factory,
            $this->presenter_builder,
            $this->kanban_actions_checker,
        );

        $this->user = UserTestBuilder::aUser()->build();
    }

    public function testItReturnsNullIfKanbanDoesNotExist(): void
    {
        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willThrowException(new KanbanNotFoundException());

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAccessToTheKanban(): void
    {
        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willThrowException(new KanbanCannotAccessException());

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoSemantic(): void
    {
        $kanban = $this->buildKanbanMock();

        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willReturn($kanban);

        $this->kanban_actions_checker
            ->expects(self::once())
            ->method('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->willThrowException(new \Tuleap\Kanban\KanbanSemanticStatusNotDefinedException());

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoTracker(): void
    {
        $kanban = $this->buildKanbanMock();

        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willReturn($kanban);

        $this->kanban_actions_checker
            ->expects(self::once())
            ->method('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->willThrowException(new \Tuleap\Kanban\KanbanTrackerNotDefinedException());

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoPerm(): void
    {
        $kanban = $this->buildKanbanMock();

        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willReturn($kanban);

        $this->kanban_actions_checker
            ->expects(self::once())
            ->method('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->willThrowException(new KanbanUserCantAddArtifactException());

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfKanbanDoesNotHaveTracker(): void
    {
        $kanban = $this->buildKanbanMock();

        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willReturn($kanban);

        $this->kanban_actions_checker
            ->expects(self::once())
            ->method('checkUserCanAddArtifact')
            ->with($this->user, $kanban);

        $this->tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn(null);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsASection(): void
    {
        $kanban = $this->buildKanbanMock();

        $this->kanban_factory
            ->expects(self::once())
            ->method('getKanban')
            ->with($this->user, 101)
            ->willReturn($kanban);

        $this->kanban_actions_checker
            ->expects(self::once())
            ->method('checkUserCanAddArtifact')
            ->with($this->user, $kanban);

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(102);
        $tracker->method('getSubmitUrl')->willReturn('/path/to/102');
        $tracker->method('getItemName')->willReturn('bug');

        $this->tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn($tracker);

        $section = $this->provider->getSectionByKanbanId(101, $this->user);
        self::assertNotNull($section);
        self::assertEquals('Kanban', $section->label);
        self::assertCount(1, $section->links);
    }

    /**
     * @return mixed
     */
    private function buildKanbanMock(): Kanban&MockObject
    {
        $kanban = $this->createMock(\Tuleap\Kanban\Kanban::class);
        $kanban->method('getTrackerId')->willReturn(42);
        return $kanban;
    }
}
