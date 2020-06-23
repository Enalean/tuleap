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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PaneInfoCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PaneInfoCollector
     */
    private $collector;

    protected function setUp(): void
    {
        $this->collector = new PaneInfoCollector(
            Mockery::mock(\Planning_Milestone::class),
            null,
            [],
            null,
            Mockery::mock(\PFUser::class),
        );
    }

    public function testAddPaneAfterEmptyArray(): void
    {
        $pane = $this->getPaneInfo('taskboard');

        $this->collector->addPaneAfter('cardwall', $pane);

        $this->assertSame(
            [
                $pane
            ],
            $this->collector->getPanes()
        );
    }

    public function testAddPaneAfterOneElement(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');

        $this->collector->addPane($pane_cardwall);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        $this->assertSame(
            [
                $pane_cardwall,
                $pane_taskboard
            ],
            $this->collector->getPanes()
        );
    }

    public function testAddPaneAfterTwoElements(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = $this->getPaneInfo('frs');

        $this->collector->addPane($pane_frs);
        $this->collector->addPane($pane_cardwall);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        $this->assertSame(
            [
                $pane_frs,
                $pane_cardwall,
                $pane_taskboard
            ],
            $this->collector->getPanes()
        );
    }

    public function testAddPaneAfterInTheMiddle(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = $this->getPaneInfo('frs');

        $this->collector->addPane($pane_cardwall);
        $this->collector->addPane($pane_frs);

        $this->collector->addPaneAfter('cardwall', $pane_taskboard);

        $this->assertSame(
            [
                $pane_cardwall,
                $pane_taskboard,
                $pane_frs
            ],
            $this->collector->getPanes()
        );
    }

    public function testItReturnsExternalPanesAtTheEnd(): void
    {
        $pane_taskboard = $this->getPaneInfo('taskboard');
        $pane_cardwall  = $this->getPaneInfo('cardwall');
        $pane_frs       = $this->getExternalPaneInfo('frs');

        $this->collector->addPane($pane_cardwall);
        $this->collector->addPane($pane_frs);
        $this->collector->addPane($pane_taskboard);

        $this->assertSame(
            [
                $pane_cardwall,
                $pane_taskboard,
                $pane_frs
            ],
            $this->collector->getPanes()
        );
    }

    public function testItReturnsNoActivePane(): void
    {
        $active_pane = null;

        $collector = new PaneInfoCollector(
            Mockery::mock(\Planning_Milestone::class),
            null,
            [],
            $active_pane,
            Mockery::mock(\PFUser::class),
        );

        $this->assertNull($collector->getActivePane());
    }

    public function testItReturnsDefaultActivePane(): void
    {
        $active_pane = Mockery::mock(AgileDashboard_Pane::class)
            ->shouldReceive(['getIdentifier' => 'ad'])
            ->getMock();

        $collector = new PaneInfoCollector(
            Mockery::mock(\Planning_Milestone::class),
            null,
            [],
            $active_pane,
            Mockery::mock(\PFUser::class),
        );

        $this->assertEquals($active_pane, $collector->getActivePane());
    }

    public function testItReturnsActivePaneProvidedByBuilder(): void
    {
        $default_active_pane = Mockery::mock(AgileDashboard_Pane::class)
            ->shouldReceive(['getIdentifier' => 'ad'])
            ->getMock();

        $collector = new PaneInfoCollector(
            Mockery::mock(\Planning_Milestone::class),
            null,
            [],
            $default_active_pane,
            Mockery::mock(\PFUser::class),
        );

        $collector->setActivePaneBuilder(
            static function () {
                return Mockery::mock(AgileDashboard_Pane::class)
                    ->shouldReceive(['getIdentifier' => 'taskboard'])
                    ->getMock();
            }
        );


        $default_active_pane = $collector->getActivePane();
        $this->assertEquals('taskboard', $default_active_pane->getIdentifier());
    }

    private function getPaneInfo(string $identifier): PaneInfo
    {
        return Mockery::mock(PaneInfo::class)
            ->shouldReceive([
                'getIdentifier'  => $identifier,
                'isExternalLink' => false,
            ])
            ->getMock();
    }

    private function getExternalPaneInfo(string $identifier): PaneInfo
    {
        return Mockery::mock(PaneInfo::class)
            ->shouldReceive([
                'getIdentifier'  => $identifier,
                'isExternalLink' => true,
            ])
            ->getMock();
    }
}
