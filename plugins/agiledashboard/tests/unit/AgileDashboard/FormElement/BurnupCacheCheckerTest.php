<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;

require_once __DIR__ . '/../../bootstrap.php';

class BurnupCacheCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ChartCachedDaysComparator|MockInterface
     */
    private $cache_days_comparator;
    /**
     * @var BurnupCacheGenerator|MockInterface
     */
    private $cache_generator;
    /**
     * @var PFUser
     */
    private $user;
    /**
     * @var \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var DatePeriodWithoutWeekEnd
     */
    private $date_period;
    /**
     * @var BurnupCacheChecker
     */
    private $burnup_cache_Checker;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $chart_value_checker;

    private $burnup_cache_dao;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache_generator       = Mockery::spy(BurnupCacheGenerator::class);
        $this->chart_value_checker   = Mockery::mock(ChartConfigurationValueChecker::class);
        $this->burnup_cache_dao      = Mockery::mock(BurnupCacheDao::class);
        $this->cache_days_comparator = Mockery::mock(ChartCachedDaysComparator::class);
        $this->burnup_cache_Checker  = new BurnupCacheChecker(
            $this->cache_generator,
            $this->chart_value_checker,
            $this->burnup_cache_dao,
            $this->cache_days_comparator
        );

        $this->artifact = Mockery::mock(Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(101);

        $start_date        = new \DateTime();
        $duration          = 10;
        $this->date_period = DatePeriodWithoutWeekEnd::buildFromDuration($start_date->getTimestamp(), $duration);

        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);
    }

    public function testItReturnsFalseWhenStartDateFieldIsNotReadable()
    {
        $this->chart_value_checker->shouldReceive('hasStartDate')->andReturnFalse();

        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user)
        );
    }

    public function testItReturnsTrueWhenBurnupIsAlreadyUnderCalculation()
    {
        $this->chart_value_checker->shouldReceive('hasStartDate')->andReturnTrue();

        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturnTrue();
        $this->burnup_cache_dao->shouldReceive('getNumberOfCachedDays')->andReturn([
            'cached_days' => 1,
        ]);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->andReturnFalse();

        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user)
        );
    }

    public function testItReturnsTrueAndSendAnEventWhenCacheIsIncompleteForBurnup()
    {
        $this->chart_value_checker->shouldReceive('hasStartDate')->andReturnTrue();

        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturnFalse();
        $this->burnup_cache_dao->shouldReceive('getNumberOfCachedDays')->andReturn([
            'cached_days' => 1,
        ]);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->andReturnFalse();

        $this->cache_generator->shouldReceive('forceBurnupCacheGeneration')->once();

        $this->assertTrue(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user)
        );
    }

    public function testItReturnsFalseWhenBurnupHasNoNeedToBeComputed()
    {
        $this->chart_value_checker->shouldReceive('hasStartDate')->andReturnTrue();

        $this->cache_generator->shouldReceive('isCacheBurnupAlreadyAsked')->with($this->artifact)->andReturnFalse();
        $this->burnup_cache_dao->shouldReceive('getNumberOfCachedDays')->andReturn([
            'cached_days' => 1,
        ]);
        $this->cache_days_comparator->shouldReceive('isNumberOfCachedDaysExpected')
            ->with($this->date_period, 1)
            ->andReturnTrue();

        $this->cache_generator->shouldReceive('forceBurnupCacheGeneration')->with($this->artifact->getId())->never();

        $this->assertFalse(
            $this->burnup_cache_Checker->isBurnupUnderCalculation($this->artifact, $this->date_period, $this->user)
        );
    }
}
