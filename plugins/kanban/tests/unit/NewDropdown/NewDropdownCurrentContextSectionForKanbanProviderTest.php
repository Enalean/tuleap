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

use Mockery;
use Tuleap\Kanban\KanbanActionsChecker;
use Tuleap\Kanban\KanbanCannotAccessException;
use Tuleap\Kanban\KanbanNotFoundException;
use Tuleap\Kanban\KanbanUserCantAddArtifactException;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

final class NewDropdownCurrentContextSectionForKanbanProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Tuleap\Kanban\KanbanFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $kanban_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerNewDropdownLinkPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var KanbanActionsChecker|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $kanban_actions_checker;
    /**
     * @var NewDropdownCurrentContextSectionForKanbanProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->kanban_factory         = Mockery::mock(\Tuleap\Kanban\KanbanFactory::class);
        $this->tracker_factory        = Mockery::mock(\TrackerFactory::class);
        $this->presenter_builder      = new TrackerNewDropdownLinkPresenterBuilder();
        $this->kanban_actions_checker = Mockery::mock(KanbanActionsChecker::class);

        $this->provider = new NewDropdownCurrentContextSectionForKanbanProvider(
            $this->kanban_factory,
            $this->tracker_factory,
            $this->presenter_builder,
            $this->kanban_actions_checker,
        );

        $this->user = Mockery::mock(\PFUser::class);
    }

    public function testItReturnsNullIfKanbanDoesNotExist(): void
    {
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andThrow(KanbanNotFoundException::class);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAccessToTheKanban(): void
    {
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andThrow(KanbanCannotAccessException::class);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoSemantic(): void
    {
        $kanban = Mockery::mock(\Tuleap\Kanban\Kanban::class)
            ->shouldReceive(['getTrackerId' => 42])
            ->getMock();

        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andReturn($kanban);

        $this->kanban_actions_checker
            ->shouldReceive('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->once()
            ->andThrow(\Tuleap\Kanban\KanbanSemanticStatusNotDefinedException::class);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoTracker(): void
    {
        $kanban = Mockery::mock(\Tuleap\Kanban\Kanban::class)
            ->shouldReceive(['getTrackerId' => 42])
            ->getMock();

        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andReturn($kanban);

        $this->kanban_actions_checker
            ->shouldReceive('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->once()
            ->andThrow(\Tuleap\Kanban\KanbanTrackerNotDefinedException::class);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfUserCannotAddArtifactInKanbanDueToNoPerm(): void
    {
        $kanban = Mockery::mock(\Tuleap\Kanban\Kanban::class)
            ->shouldReceive(['getTrackerId' => 42])
            ->getMock();

        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andReturn($kanban);

        $this->kanban_actions_checker
            ->shouldReceive('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->once()
            ->andThrow(KanbanUserCantAddArtifactException::class);

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsNullIfKanbanDoesNotHaveTracker(): void
    {
        $kanban = Mockery::mock(\Tuleap\Kanban\Kanban::class)
            ->shouldReceive(['getTrackerId' => 42])
            ->getMock();

        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andReturn($kanban);

        $this->kanban_actions_checker
            ->shouldReceive('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->once();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(42)
            ->andReturnNull();

        self::assertNull(
            $this->provider->getSectionByKanbanId(101, $this->user)
        );
    }

    public function testItReturnsASection(): void
    {
        $kanban = Mockery::mock(\Tuleap\Kanban\Kanban::class)
            ->shouldReceive(['getTrackerId' => 42])
            ->getMock();

        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 101)
            ->once()
            ->andReturn($kanban);

        $this->kanban_actions_checker
            ->shouldReceive('checkUserCanAddArtifact')
            ->with($this->user, $kanban)
            ->once();

        $tracker = Mockery::mock(\Tracker::class)
            ->shouldReceive([
                'getId' => 102,
                'getSubmitUrl' => '/path/to/102',
                'getItemName' => 'bug',
            ])
            ->getMock();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(42)
            ->andReturn($tracker);

        $section = $this->provider->getSectionByKanbanId(101, $this->user);
        self::assertEquals('Kanban', $section->label);
        self::assertCount(1, $section->links);
    }
}
