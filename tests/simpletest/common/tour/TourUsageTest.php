<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tuleap_TourUsageTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();
        $this->stats_dao  = mock('Tuleap_TourUsageStatsDao');
        $this->user       = stub('PFUser')->getId()->returns(123);
        $this->tour_usage = new Tuleap_TourUsage($this->stats_dao);
        $this->tour       = new Tuleap_TourUsageTest_FakeTour();
    }

    public function itSavesInUserPreferencesThatTheTourIsExecuted()
    {
        expect($this->user)->setPreference($this->tour->name, true)->once();

        $this->tour_usage->endTour($this->user, $this->tour, $this->current_step);
    }

    public function itStoresUsageStatistics()
    {
        expect($this->stats_dao)
            ->save(
                $this->user->getId(),
                $this->tour->name,
                count($this->tour->steps),
                $this->current_step,
                true
            )->once();

        $this->tour_usage->endTour($this->user, $this->tour, $this->current_step);
    }

    public function itStoresUsageStatisticsWhenAStepIsShown()
    {
        expect($this->stats_dao)
            ->save(
                $this->user->getId(),
                $this->tour->name,
                count($this->tour->steps),
                $this->current_step,
                false
            )->once();

        $this->tour_usage->stepShown($this->user, $this->tour, $this->current_step);
    }
}

class Tuleap_TourUsageTest_FakeTour extends Tuleap_Tour
{
    public function __construct()
    {
        parent::__construct(
            'le-welcome-tour',
            array(
                new Tuleap_Tour_Step('title 1', 'content 1'),
                new Tuleap_Tour_Step('title 2', 'content 2')
            )
        );
    }
}
