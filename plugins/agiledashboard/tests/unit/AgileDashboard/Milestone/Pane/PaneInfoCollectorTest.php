<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane;

use AgileDashboard_Pane;
use Planning_NoMilestone;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\FRS\AgileDashboardPaneInfo;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Milestone\PaneInfo;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PaneInfoCollectorTest extends TestCase
{
    private PaneInfoCollector $collector;

    #[\Override]
    protected function setUp(): void
    {
        $this->collector = new PaneInfoCollector(
            new Planning_NoMilestone(ProjectTestBuilder::aProject()->build(), PlanningBuilder::aPlanning(101)->build()),
            null,
            [],
            null,
            UserTestBuilder::buildWithDefaults(),
        );
    }

    public function testAddPaneAfterEmptyArray(): void
    {
        $pane = $this->getPaneInfo('taskboard');

        $this->collector->addPaneAfter('cardwall', $pane);

        self::assertSame([$pane], $this->collector->getPanes());
    }

    public function testAddPaneAfterOneElement(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');

        $this->collector->addPane($pane_cardwall);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        self::assertSame([
            $pane_cardwall,
            $pane_taskboard,
        ], $this->collector->getPanes());
    }

    public function testAddPaneAfterTwoElements(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = $this->getPaneInfo('frs');

        $this->collector->addPane($pane_frs);
        $this->collector->addPane($pane_cardwall);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        self::assertSame([
            $pane_frs,
            $pane_cardwall,
            $pane_taskboard,
        ], $this->collector->getPanes());
    }

    public function testAddPaneAfterInTheMiddle(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = $this->getPaneInfo('frs');

        $this->collector->addPane($pane_cardwall);
        $this->collector->addPane($pane_frs);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        self::assertSame([
            $pane_cardwall,
            $pane_taskboard,
            $pane_frs,
        ], $this->collector->getPanes());
    }

    public function testItReturnsExternalPanesAtTheEnd(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = new AgileDashboardPaneInfo(1);

        $this->collector->addPane($pane_cardwall);
        $this->collector->addPane($pane_frs);
        $this->collector->addPane($pane_taskboard);

        self::assertSame([
            $pane_cardwall,
            $pane_taskboard,
            $pane_frs,
        ], $this->collector->getPanes());
    }

    public function testItReturnsNoActivePane(): void
    {
        $active_pane = null;

        $collector = new PaneInfoCollector(
            new Planning_NoMilestone(ProjectTestBuilder::aProject()->build(), PlanningBuilder::aPlanning(101)->build()),
            null,
            [],
            $active_pane,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($collector->getActivePane());
    }

    public function testItReturnsDefaultActivePane(): void
    {
        $active_pane = $this->createMock(AgileDashboard_Pane::class);
        $active_pane->method('getIdentifier')->willReturn('ad');

        $collector = new PaneInfoCollector(
            new Planning_NoMilestone(ProjectTestBuilder::aProject()->build(), PlanningBuilder::aPlanning(101)->build()),
            null,
            [],
            $active_pane,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertEquals($active_pane, $collector->getActivePane());
    }

    public function testItReturnsActivePaneProvidedByBuilder(): void
    {
        $default_active_pane = $this->createMock(AgileDashboard_Pane::class);
        $default_active_pane->method('getIdentifier')->willReturn('ad');

        $collector = new PaneInfoCollector(
            new Planning_NoMilestone(ProjectTestBuilder::aProject()->build(), PlanningBuilder::aPlanning(101)->build()),
            null,
            [],
            $default_active_pane,
            UserTestBuilder::buildWithDefaults(),
        );

        $collector->setActivePaneBuilder(function () {
            $pane = $this->createMock(AgileDashboard_Pane::class);
            $pane->method('getIdentifier')->willReturn('taskboard');

            return $pane;
        });


        $default_active_pane = $collector->getActivePane();
        self::assertEquals('taskboard', $default_active_pane->getIdentifier());
    }

    private function getPaneInfo(string $identifier): PaneInfo
    {
        $pane_info = $this->createMock(PaneInfo::class);
        $pane_info->method('getIdentifier')->willReturn($identifier);
        $pane_info->method('isExternalLink')->willReturn(false);

        return $pane_info;
    }
}
