<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
use Psr\Log\LoggerInterface;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsInfo;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurnupDataBuilder
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BurnupCacheChecker
     */
    private $cache_checker;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $chart_configuration_value_retriever;

    /**
     * @var BurnupCacheDao
     */
    private $burnup_cache_dao;

    /**
     * @var BurnupCalculator
     */
    private $burnup_calculator;

    /**
     * @var CountElementsModeChecker
     */
    private $mode_checker;

    /**
     * @var CountElementsCacheDao
     */
    private $count_elements_cache_dao;

    /**
     * @var CountElementsCalculator
     */
    private $count_elements_calculator;

    public function __construct(
        LoggerInterface $logger,
        BurnupCacheChecker $cache_checker,
        ChartConfigurationValueRetriever $chart_configuration_value_retriever,
        BurnupCacheDao $burnup_cache_dao,
        BurnupCalculator $burnup_calculator,
        CountElementsCacheDao $count_elements_cache_dao,
        CountElementsCalculator $count_elements_calculator,
        CountElementsModeChecker $mode_checker
    ) {
        $this->logger                              = $logger;
        $this->cache_checker                       = $cache_checker;
        $this->chart_configuration_value_retriever = $chart_configuration_value_retriever;
        $this->burnup_cache_dao                    = $burnup_cache_dao;
        $this->burnup_calculator                   = $burnup_calculator;
        $this->mode_checker                        = $mode_checker;
        $this->count_elements_cache_dao            = $count_elements_cache_dao;
        $this->count_elements_calculator           = $count_elements_calculator;
    }

    /**
     * @return BurnupData
     */
    public function buildBurnupData(Tracker_Artifact $artifact, \PFUser $user)
    {
        $time_period = $this->chart_configuration_value_retriever->getTimePeriod($artifact, $user);

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
        $start->setTimestamp((int) $time_period->getStartDate());
        $start->setTime(0, 0, 0);

        $this->logger->debug("Start date after updating timezone: " . $start->getTimestamp());

        $time_period          = TimePeriodWithoutWeekEnd::buildFromDuration($start->getTimestamp(), $time_period->getDuration());
        $is_under_calculation = $this->cache_checker->isBurnupUnderCalculation($artifact, $time_period, $user);
        $burnup_data          = new BurnupData($time_period, $is_under_calculation);

        if (! $is_under_calculation) {
            $this->addEfforts($artifact, $burnup_data);

            if ($this->mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject())) {
                $this->addCountElements($artifact, $burnup_data);
            }
        }

        $this->logger->info("End calculating burnup " . $artifact->getId());
        date_default_timezone_set($user_timezone);

        return $burnup_data;
    }

    private function addEfforts(Tracker_Artifact $artifact, BurnupData $burnup_data)
    {
        $cached_days_result = $this->burnup_cache_dao->searchCachedDaysValuesByArtifactId(
            $artifact->getId(),
            $burnup_data->getTimePeriod()->getStartDate()
        );

        foreach ($cached_days_result as $cached_day) {
            $effort = new BurnupEffort($cached_day['team_effort'], $cached_day['total_effort']);
            $burnup_data->addEffort($effort, $cached_day['timestamp']);
        }

        if ($burnup_data->getTimePeriod()->isTodayWithinTimePeriod()) {
            $now    = time();
            $effort = $this->burnup_calculator->getValue($artifact->getId(), $now);
            $burnup_data->addEffort($effort, $now);
        }
    }

    private function addCountElements(Tracker_Artifact $artifact, BurnupData $burnup_data): void
    {
        $cached_days_result = $this->count_elements_cache_dao->searchCachedDaysValuesByArtifactId(
            (int) $artifact->getId(),
            (int) $burnup_data->getTimePeriod()->getStartDate()
        );

        if (is_array($cached_days_result)) {
            foreach ($cached_days_result as $cached_day) {
                $count_elements = new CountElementsInfo($cached_day['closed_subelements'], $cached_day['total_subelements']);
                $burnup_data->addCountElements($count_elements, (int) $cached_day['timestamp']);
            }
        }

        if ($burnup_data->getTimePeriod()->isTodayWithinTimePeriod()) {
            $now    = time();
            $count_elements = $this->count_elements_calculator->getValue((int) $artifact->getId(), $now);
            $burnup_data->addCountElements($count_elements, $now);
        }
    }
}
