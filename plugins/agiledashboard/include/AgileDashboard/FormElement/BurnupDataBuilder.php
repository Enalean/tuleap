<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use DateTime;
use Logger;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\FieldCalculator;

class BurnupDataBuilder
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var BurnupCacheChecker
     */
    private $cache_checker;
    /**
     * @var ChartConfigurationValueRetriever
     */
    private $field_retriever;
    /**
     * @var BurnupCacheDao
     */
    private $burnup_cache_dao;
    /**
     * @var FieldCalculator
     */
    private $team_effort_team_calculator;
    /**
     * @var FieldCalculator
     */
    private $total_effort_team_calculator;

    public function __construct(
        Logger $logger,
        BurnupCacheChecker $cache_checker,
        ChartConfigurationValueRetriever $field_retriever,
        BurnupCacheDao $burnup_cache_dao,
        FieldCalculator $team_effort_team_calculator,
        FieldCalculator $total_effort_team_calculator
    ) {
        $this->logger                       = $logger;
        $this->cache_checker                = $cache_checker;
        $this->field_retriever              = $field_retriever;
        $this->burnup_cache_dao             = $burnup_cache_dao;
        $this->team_effort_team_calculator  = $team_effort_team_calculator;
        $this->total_effort_team_calculator = $total_effort_team_calculator;
    }

    /**
     * @return BurnupData
     */
    public function buildBurnupData(Tracker_Artifact $artifact, \PFUser $user)
    {
        $start_date  = $this->field_retriever->getStartDate($artifact, $user);
        $duration    = $this->field_retriever->getDuration($artifact, $user);
        $time_period = new TimePeriodWithoutWeekEnd($start_date, $duration);

        return $this->getBurnupData(
            $artifact,
            $time_period,
            $user
        );
    }

    /**
     * @return BurnupData
     */
    private function getBurnupData(Tracker_Artifact $artifact, TimePeriodWithoutWeekEnd $time_period, \PFUser $user)
    {
        $user_timezone   = date_default_timezone_get();
        $server_timezone = TimezoneRetriever::getServerTimezone();
        date_default_timezone_set($server_timezone);

        $start = new  DateTime();
        $start->setTimestamp($time_period->getStartDate());
        $start->setTime(0, 0, 0);

        $this->logger->debug("Start date after updating timezone: " . $start->getTimestamp());

        $time_period          = new TimePeriodWithoutWeekEnd($start->getTimestamp(), $time_period->getDuration());
        $is_under_calculation = $this->cache_checker->isBurnupUnderCalculation($artifact, $time_period, $user);
        $burnup_data          = new BurnupData($is_under_calculation);

        if (! $is_under_calculation) {
            $this->addEfforts($artifact, $burnup_data, $time_period);
        }

        $this->logger->info("End calculating burnup " . $artifact->getId());
        date_default_timezone_set($user_timezone);

        return $burnup_data;
    }

    private function addEfforts(Tracker_Artifact $artifact, BurnupData $burnup_data, TimePeriodWithoutWeekEnd $time_period)
    {
        $cached_days_result = $this->burnup_cache_dao->searchCachedDaysValuesByArtifactId($artifact->getId());
        foreach ($cached_days_result as $cached_day) {
            $effort = new BurnupEffort($cached_day['team_effort'], $cached_day['total_effort']);
            $burnup_data->addEffort($effort, $cached_day['timestamp']);
        }

        $now = time();
        if ($time_period->getEndDate() > $now) {
            $burnup_data->addEffort($this->getCurrentEffort($artifact), $now);
        }
    }

    /**
     * @return BurnupEffort
     */
    private function getCurrentEffort(Tracker_Artifact $artifact)
    {
        $stop_on_manual_value = true;

        $team_effort = $this->team_effort_team_calculator->calculate(
            array($artifact->getId()),
            null,
            $stop_on_manual_value,
            null,
            null
        );

        $total_effort = $this->total_effort_team_calculator->calculate(
            array($artifact->getId()),
            null,
            $stop_on_manual_value,
            null,
            null
        );

        return new BurnupEffort($team_effort, $total_effort);
    }
}
