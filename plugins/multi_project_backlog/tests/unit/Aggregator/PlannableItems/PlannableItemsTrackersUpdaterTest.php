<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\PlannableItems;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Tuleap\MultiProjectBacklog\Contributor\ContributorDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class PlannableItemsTrackersUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PlannableItemsTrackersUpdater
     */
    private $updater;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ContributorDao
     */
    private $contributor_dao;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PlannableItemsTrackersDao
     */
    private $plannable_items_trackers_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contributor_dao              = Mockery::mock(ContributorDao::class);
        $this->plannable_items_trackers_dao = Mockery::mock(PlannableItemsTrackersDao::class);

        $this->updater = new PlannableItemsTrackersUpdater(
            $this->contributor_dao,
            $this->plannable_items_trackers_dao,
            new DBTransactionExecutorPassthrough()
        );
    }

    public function testItUpdatesThePlannableItemsTrackers(): void
    {
        $planning = new Planning(1, 'Release Planning', 104, 'Release Backlog', 'Sprint Plan', [302, 504]);

        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(104)
            ->once()
            ->andReturnTrue();

        $this->plannable_items_trackers_dao->shouldReceive('deletePlannableItemsTrackerIdsOfAGivenContributorProject')
            ->once();

        $this->contributor_dao->shouldReceive('getAggregatorProjectsOfAGivenContributorProject')
            ->with(104)
            ->once()
            ->andReturn([
                ['aggregator_project_id' => 102]
            ]);

        $this->plannable_items_trackers_dao->shouldReceive('addPlannableItemsTrackerIds')
            ->with(
                102,
                [302, 504]
            )
            ->once();

        $this->updater->updatePlannableItemsTrackersFromPlanning($planning);
    }

    public function testItDoesNothingIfThePlanningIsNotInAContributorProject(): void
    {
        $planning = new Planning(1, 'Release Planning', 104, 'Release Backlog', 'Sprint Plan', [302, 504]);

        $this->contributor_dao->shouldReceive('isProjectAContributorProject')
            ->with(104)
            ->once()
            ->andReturnFalse();

        $this->plannable_items_trackers_dao->shouldNotReceive('deletePlannableItemsTrackerIdsOfAGivenContributorProject');
        $this->contributor_dao->shouldNotReceive('getAggregatorProjectsOfAGivenContributorProject');
        $this->plannable_items_trackers_dao->shouldNotReceive('addPlannableItemsTrackerIds');

        $this->updater->updatePlannableItemsTrackersFromPlanning($planning);
    }
}
