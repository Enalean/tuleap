<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Tour;

use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap_Tour;
use Tuleap_Tour_Step;
use Tuleap_TourUsage;
use Tuleap_TourUsageStatsDao;

final class TourUsageTest extends TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var PFUser */
    private $user;

    /** @var Tuleap_TourUsage */
    private $tour_usage;

    /** @var Tuleap_TourUsageStatsDao */
    private $stats_dao;

    /** @var int */
    private $current_step = 2;

    /** @var Tuleap_Tour */
    private $tour;

    protected function setUp(): void
    {
        $this->stats_dao  = \Mockery::spy(Tuleap_TourUsageStatsDao::class);
        $this->user       = \Mockery::spy(PFUser::class)->shouldReceive('getId')->andReturns(123)->getMock();
        $this->tour_usage = new Tuleap_TourUsage($this->stats_dao);
        $this->tour       = new Tuleap_Tour(
            'le-welcome-tour',
            [
                new Tuleap_Tour_Step('title 1', 'content 1'),
                new Tuleap_Tour_Step('title 2', 'content 2')
            ]
        );
    }

    public function testItSavesInUserPreferencesThatTheTourIsExecuted(): void
    {
        $this->user->shouldReceive('setPreference')->with($this->tour->name, true)->once();

        $this->tour_usage->endTour($this->user, $this->tour, $this->current_step);
    }

    public function testItStoresUsageStatistics(): void
    {
        $this->stats_dao->shouldReceive('save')->with($this->user->getId(), $this->tour->name, count($this->tour->steps), $this->current_step, true)->once();

        $this->tour_usage->endTour($this->user, $this->tour, $this->current_step);
    }

    public function testItStoresUsageStatisticsWhenAStepIsShown(): void
    {
        $this->stats_dao->shouldReceive('save')->with($this->user->getId(), $this->tour->name, count($this->tour->steps), $this->current_step, false)->once();

        $this->tour_usage->stepShown($this->user, $this->tour, $this->current_step);
    }
}
