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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement;

use DateTime;
use PFUser;
use PlanningFactory;
use Psr\Log\LoggerInterface;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsInfo;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurnupDataBuilder
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BurnupCacheChecker $cache_checker,
        private readonly ChartConfigurationValueRetriever $chart_configuration_value_retriever,
        private readonly BurnupCacheDao $burnup_cache_dao,
        private readonly BurnupCalculator $burnup_calculator,
        private readonly CountElementsCacheDao $count_elements_cache_dao,
        private readonly CountElementsCalculator $count_elements_calculator,
        private readonly CountElementsModeChecker $mode_checker,
        private readonly PlanningDao $planning_dao,
        private readonly PlanningFactory $planning_factory,
        private readonly BurnupCacheDateRetriever $date_retriever,
    ) {
    }

    public function buildBurnupData(Artifact $artifact, PFUser $user): BurnupData
    {
        $date_period = $this->chart_configuration_value_retriever->getDatePeriod($artifact, $user);

        return $this->getBurnupData(
            $artifact,
            $date_period,
            $user
        );
    }

    private function getBurnupData(Artifact $artifact, DatePeriodWithOpenDays $date_period, PFUser $user): BurnupData
    {
        $user_timezone   = date_default_timezone_get();
        $server_timezone = TimezoneRetriever::getServerTimezone();
        date_default_timezone_set($server_timezone);

        $this->logger->debug('Start date: ' . (string) $date_period->getStartDate());

        $is_under_calculation = $this->cache_checker->isBurnupUnderCalculation(
            $artifact,
            $this->date_retriever->getWorkedDaysToCacheForPeriod($date_period, new DateTime('yesterday')),
            $user,
            $date_period
        );
        $burnup_data          = new BurnupData($date_period, $is_under_calculation);

        $planning_infos = $this->planning_dao->searchByMilestoneTrackerId($artifact->getTrackerId());
        if (! $is_under_calculation && $planning_infos) {
            $backlog_trackers_ids = $this->planning_factory->getBacklogTrackersIds($planning_infos['id']);
            $this->addEfforts($artifact, $burnup_data, $backlog_trackers_ids);

            if ($this->mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject())) {
                $this->addCountElements($artifact, $burnup_data, $backlog_trackers_ids);
            }
        }

        $this->logger->info('End calculating burnup ' . $artifact->getId());
        date_default_timezone_set($user_timezone);

        return $burnup_data;
    }

    private function addEfforts(Artifact $artifact, BurnupData $burnup_data, array $backlog_trackers_ids): void
    {
        $start_timestamp = $burnup_data->getDatePeriod()->getStartDate();
        if ($start_timestamp === null) {
            return;
        }
        $cached_days_result = $this->burnup_cache_dao->searchCachedDaysValuesByArtifactId(
            $artifact->getId(),
            $start_timestamp
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
