<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\Widget;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TestManagement\Type\TypeCoveredByPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RoadmapWidgetPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsAPresenterThatExcludesIsChildFromVisibleNatures(): void
    {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([
            new TypeIsChildPresenter(),
            new TypeCoveredByPresenter(),
        ]);

        $user = UserTestBuilder::aUser()->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);

        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, null, null, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertFalse($presenter->should_load_lvl1_iterations);
        self::assertFalse($presenter->should_load_lvl2_iterations);

        $natures = \json_decode($presenter->visible_natures, true);
        self::assertCount(1, $natures);
        self::assertEquals(TypeCoveredByPresenter::TYPE_COVERED_BY, $natures[0]['shortname']);
    }

    public function testItInformsThatIterationsAtLevel1ShouldBeLoaded(): void
    {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $iteration_tracker = $this->createMock(\Tracker::class);
        $iteration_tracker->method('isActive')->willReturn(true);
        $iteration_tracker->method('userCanView')->willReturn(true);

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn($iteration_tracker);


        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, 42, null, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertTrue($presenter->should_load_lvl1_iterations);
        self::assertFalse($presenter->should_load_lvl2_iterations);
    }

    public function testItInformsThatIterationsAtLevel2ShouldBeLoaded(): void
    {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $iteration_tracker = $this->createMock(\Tracker::class);
        $iteration_tracker->method('isActive')->willReturn(true);
        $iteration_tracker->method('userCanView')->willReturn(true);

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn($iteration_tracker);


        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, null, 42, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertFalse($presenter->should_load_lvl1_iterations);
        self::assertTrue($presenter->should_load_lvl2_iterations);
    }

    /**
     * @testWith [42, null]
     *           [null, 42]
     */
    public function testItWillNotLoadIterationsIfTrackerDoesNotExists(
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
    ): void {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn(null);


        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, 42, null, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertFalse($presenter->should_load_lvl1_iterations);
        self::assertFalse($presenter->should_load_lvl2_iterations);
    }

    /**
     * @testWith [42, null]
     *           [null, 42]
     */
    public function testItWillNotLoadIterationsIfTrackerIsNotActive(
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
    ): void {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $iteration_tracker = $this->createMock(\Tracker::class);
        $iteration_tracker->method('isActive')->willReturn(false);

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn($iteration_tracker);


        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, 42, null, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertFalse($presenter->should_load_lvl1_iterations);
        self::assertFalse($presenter->should_load_lvl2_iterations);
    }

    /**
     * @testWith [42, null]
     *           [null, 42]
     */
    public function testItWillNotLoadIterationsIfTrackerIsNotReadable(
        ?int $lvl1_iteration_tracker_id,
        ?int $lvl2_iteration_tracker_id,
    ): void {
        $nature_presenter_factory = $this->createMock(TypePresenterFactory::class);
        $nature_presenter_factory->method('getOnlyVisibleTypes')->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $iteration_tracker = $this->createMock(\Tracker::class);
        $iteration_tracker->method('isActive')->willReturn(true);
        $iteration_tracker->method('userCanView')->willReturn(false);

        $tracker_factory = $this->createMock(\TrackerFactory::class);
        $tracker_factory
            ->method('getTrackerById')
            ->with(42)
            ->willReturn($iteration_tracker);


        $builder   = new RoadmapWidgetPresenterBuilder($nature_presenter_factory, $tracker_factory);
        $presenter = $builder->getPresenter(123, 42, null, 'month', $user);
        self::assertEquals(123, $presenter->roadmap_id);
        self::assertFalse($presenter->should_load_lvl1_iterations);
        self::assertFalse($presenter->should_load_lvl2_iterations);
    }
}
