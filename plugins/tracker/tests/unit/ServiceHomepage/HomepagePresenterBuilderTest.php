<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\ServiceHomepage;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\Color\ColorName;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Tooltip\TrackerStats;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HomepagePresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_NAME = 'contemptibleness-prenotion';
    private \TrackerFactory&Stub $tracker_factory;
    private \Tracker_Migration_MigrationManager&MockObject $migration_manager;
    private bool $is_tracker_admin;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_factory   = $this->createStub(\TrackerFactory::class);
        $this->migration_manager = $this->createMock(\Tracker_Migration_MigrationManager::class);
        $this->is_tracker_admin  = false;

        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    private function build(): HomepagePresenter
    {
        $builder = new HomepagePresenterBuilder(
            $this->tracker_factory,
            $this->migration_manager
        );
        return $builder->build(
            ProjectTestBuilder::aProject()->withId(152)->withUnixName(self::PROJECT_NAME)->build(),
            UserTestBuilder::buildWithId(143),
            $this->is_tracker_admin
        );
    }

    public function testItBuildsHomepagePresenterForAdministrator(): void
    {
        $first_tracker  = $this->mockTracker(true, true);
        $second_tracker = $this->mockTracker(true, true);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([$first_tracker, $second_tracker,]);
        $this->migration_manager->method('isTrackerUnderMigration')->willReturnMap([
            [$first_tracker, false],
            [$second_tracker, false],
        ]);
        $this->is_tracker_admin = true;

        $presenter = $this->build();
        self::assertFalse($presenter->is_empty);
        self::assertTrue($presenter->is_tracker_admin);
        self::assertSame('/plugins/tracker/' . self::PROJECT_NAME . '/new', $presenter->new_tracker_uri);
        if (count($presenter->trackers) !== 2) {
            throw new \LogicException('Expected to have two presenters');
        }
        [$first_presenter, $second_presenter] = $presenter->trackers;
        self::assertTrue($first_presenter->has_statistics);
        self::assertNotNull($first_presenter->tooltip);

        self::assertTrue($second_presenter->has_statistics);
        self::assertNotNull($second_presenter->tooltip);
    }

    public function testItBuildsHomepagePresenterForOtherUsers(): void
    {
        $first_tracker  = $this->mockTracker(true, true);
        $second_tracker = $this->mockTracker(true, true);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([$first_tracker, $second_tracker,]);
        $this->migration_manager->method('isTrackerUnderMigration')->willReturnMap([
            [$first_tracker, false],
            [$second_tracker, false],
        ]);
        $this->is_tracker_admin = false;

        $presenter = $this->build();

        self::assertFalse($presenter->is_tracker_admin);
        self::assertCount(2, $presenter->trackers);
    }

    public function testItBuildsEmptyPage(): void
    {
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([]);

        $presenter = $this->build();

        self::assertTrue($presenter->is_empty);
        self::assertCount(0, $presenter->trackers);
    }

    public function testItOmitsTrackersThatUserIsNotAllowedToSee(): void
    {
        $first_tracker  = $this->mockTracker(false, false);
        $second_tracker = $this->mockTracker(true, true);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([$first_tracker, $second_tracker,]);
        $this->migration_manager->method('isTrackerUnderMigration')->willReturnMap([
            [$first_tracker, false],
            [$second_tracker, false],
        ]);

        $presenter = $this->build();

        self::assertCount(1, $presenter->trackers);
    }

    public function testItOmitsTrackersThatAreUnderMigration(): void
    {
        $first_tracker  = $this->mockTracker(true, true);
        $second_tracker = $this->mockTracker(true, true);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([$first_tracker, $second_tracker,]);
        $this->migration_manager->method('isTrackerUnderMigration')->willReturnMap([
            [$first_tracker, true],
            [$second_tracker, false],
        ]);

        $presenter = $this->build();

        self::assertCount(1, $presenter->trackers);
    }

    public function testItOmitsStatisticsForTrackersThatUserIsNotGrantedFullAccessTo(): void
    {
        $first_tracker  = $this->mockTracker(true, false);
        $second_tracker = $this->mockTracker(true, true);
        $this->tracker_factory->method('getTrackersByGroupId')->willReturn([$first_tracker, $second_tracker,]);
        $this->migration_manager->method('isTrackerUnderMigration')->willReturnMap([
            [$first_tracker, false],
            [$second_tracker, false],
        ]);

        $presenter = $this->build();

        self::assertCount(2, $presenter->trackers);
        $first_tracker = $presenter->trackers[0];
        self::assertFalse($first_tracker->has_statistics);
        self::assertNull($first_tracker->tooltip);
    }

    private function mockTracker(
        bool $user_can_view,
        bool $user_has_full_access,
    ): Stub&\Tuleap\Tracker\Tracker {
        $tracker = $this->createStub(\Tuleap\Tracker\Tracker::class);
        $tracker->method('getId')->willReturn(15);
        $tracker->method('getColor')->willReturn(ColorName::SHERWOOD_GREEN);
        $tracker->method('getName')->willReturn('task');
        $tracker->method('getDescription')->willReturn('Track development tasks');
        $tracker->method('getUri')->willReturn('/plugins/tracker/?tracker=15');
        $tracker->method('hasSemanticsStatus')->willReturn(true);
        $tracker->method('getStats')->willReturn(
            new TrackerStats(
                34,
                9,
                1389358291,
                1435446943
            )
        );
        $tracker->method('userHasFullAccess')->willReturn($user_has_full_access);
        $tracker->method('userCanView')->willReturn($user_can_view);
        return $tracker;
    }
}
