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
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsInfo;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurnupDataBuilder
{
    public function __construct(
        private LoggerInterface $logger,
        private BurnupCacheChecker $cache_checker,
        private ChartConfigurationValueRetriever $chart_configuration_value_retriever,
        private BurnupCacheDao $burnup_cache_dao,
        private BurnupCalculator $burnup_calculator,
        private CountElementsCacheDao $count_elements_cache_dao,
        private CountElementsCalculator $count_elements_calculator,
        private CountElementsModeChecker $mode_checker,
        private PlanningDao $planning_dao,
        private \PlanningFactory $planning_factory,
    ) {
    }

    /**
     * @return BurnupData
     */
    public function buildBurnupData(Artifact $artifact, \PFUser $user)
    {
        $date_period = $this->chart_configuration_value_retriever->getDatePeriod($artifact, $user);

        return $this->getBurnupData(
            $artifact,
            $date_period,
            $user
        );
    }

    /**
     * @return BurnupData
     */
    private function getBurnupData(Artifact $artifact, DatePeriodWithoutWeekEnd $date_period, \PFUser $user)
    {
        $user_timezone   = date_default_timezone_get();
        $server_timezone = TimezoneRetriever::getServerTimezone();
        date_default_timezone_set($server_timezone);

        $start = new DateTime();
        $start->setTimestamp((int) $date_period->getStartDate());
        $start->setTime(0, 0, 0);

        $this->logger->debug("Start date after updating timezone: " . $start->getTimestamp());

        $date_period          = DatePeriodWithoutWeekEnd::buildFromDuration($start->getTimestamp(), $date_period->getDuration());
        $is_under_calculation = $this->cache_checker->isBurnupUnderCalculation($artifact, $date_period, $user);
        $burnup_data          = new BurnupData($date_period, $is_under_calculation);

        $planning_infos = $this->planning_dao->searchByMilestoneTrackerId($artifact->getTrackerId());
        if (! $is_under_calculation && $planning_infos) {
            $backlog_trackers_ids = $this->planning_factory->getBacklogTrackersIds($planning_infos['id']);
            $this->addEfforts($artifact, $burnup_data, $backlog_trackers_ids);

            if ($this->mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject())) {
                $this->addCountElements($artifact, $burnup_data, $backlog_trackers_ids);
            }
        }

        $this->logger->info("End calculating burnup " . $artifact->getId());
        date_default_timezone_set($user_timezone);

        return $burnup_data;
    }

    private function addEfforts(Artifact $artifact, BurnupData $burnup_data, array $backlog_trackers_ids): void
    {
        $cached_days_result = $this->burnup_cache_dao->searchCachedDaysValuesByArtifactId(
            $artifact->getId(),
            $burnup_data->getDatePeriod()->getStartDate()
        );

        foreach ($cached_days_result as $cached_day) {
            $effort = new BurnupEffort($cached_day['team_effort'], $cached_day['total_effort']);
            $burnup_data->addEffort($effort, $cached_day['timestamp']);
        }

        if ($burnup_data->getDatePeriod()->isTodayWithinDatePeriod()) {
            $now    = time();
            $effort = $this->burnup_calculator->getValue($artifact->getId(), $now, $backlog_trackers_ids);
            $burnup_data->addEffort($effort, $now);
        }
    }

    private function addCountElements(Artifact $artifact, BurnupData $burnup_data, array $backlog_trackers_ids): void
    {
        $cached_days_result = $this->count_elements_cache_dao->searchCachedDaysValuesByArtifactId(
            $artifact->getId(),
            (int) $burnup_data->getDatePeriod()->getStartDate()
        );

        if (is_array($cached_days_result)) {
            foreach ($cached_days_result as $cached_day) {
                $count_elements = new CountElementsInfo($cached_day['closed_subelements'], $cached_day['total_subelements']);
                $burnup_data->addCountElements($count_elements, (int) $cached_day['timestamp']);
            }
        }

        if ($burnup_data->getDatePeriod()->isTodayWithinDatePeriod()) {
            $now            = time();
            $count_elements = $this->count_elements_calculator->getValue($artifact->getId(), $now, $backlog_trackers_ids);
            $burnup_data->addCountElements($count_elements, $now);
        }
    }
}
