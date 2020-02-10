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

namespace Tuleap\AgileDashboard\FormElement\SystemEvent;

use Psr\Log\LoggerInterface;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDao;

class SystemEvent_BURNUP_DAILY extends SystemEvent // @codingStandardsIgnoreLine
{
    /**
     * @var BurnupDao
     */
    private $burnup_dao;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var  BurnupCalculator
     */
    private $burnup_calculator;

    /**
     * @var BurnupCacheDao
     */
    private $cache_dao;

    /**
     * @var BurnupCacheDateRetriever
     */
    private $date_retriever;

    /**
     * @var CountElementsCalculator
     */
    private $burnup_count_elements_calculator;

    /**
     * @var CountElementsCacheDao
     */
    private $count_elements_cache_dao;

    public function verbalizeParameters($with_link)
    {
        return '-';
    }

    public function injectDependencies(
        BurnupDao $burnup_dao,
        BurnupCalculator $burnup_calculator,
        CountElementsCalculator $burnup_count_elements_calculator,
        BurnupCacheDao $cache_dao,
        CountElementsCacheDao $count_elements_cache_dao,
        LoggerInterface $logger,
        BurnupCacheDateRetriever $date_retriever
    ) {
        $this->burnup_dao                       = $burnup_dao;
        $this->logger                           = $logger;
        $this->burnup_calculator                = $burnup_calculator;
        $this->burnup_count_elements_calculator = $burnup_count_elements_calculator;
        $this->cache_dao                        = $cache_dao;
        $this->count_elements_cache_dao         = $count_elements_cache_dao;
        $this->date_retriever                   = $date_retriever;
    }

    public function process()
    {
        $this->cacheYesterdayValues();
        $this->done();

        return true;
    }

    private function cacheYesterdayValues()
    {
        $yesterday = $this->date_retriever->getYesterday();
        if (! TimePeriodWithoutWeekEnd::isNotWeekendDay($yesterday)) {
            return;
        }

        foreach ($this->burnup_dao->searchArtifactsWithBurnup() as $burnup) {
            if (empty($burnup['duration'])) {
                $burnup_period = TimePeriodWithoutWeekEnd::buildFromEndDate(
                    $burnup['start_date'],
                    $burnup['end_date'],
                    $this->logger
                );
            } else {
                $burnup_period = TimePeriodWithoutWeekEnd::buildFromDuration(
                    $burnup['start_date'],
                    $burnup['duration']
                );
            }

            $burnup_timeperiod_start_day_timestamp = $burnup_period->getStartDate();
            if ($burnup_timeperiod_start_day_timestamp !== null &&
                $yesterday < $burnup_timeperiod_start_day_timestamp
            ) {
                $this->logger->debug(
                    "Today is not in time period for artifact #" . $burnup['id'] . ', skipping.'
                );

                continue;
            }

            if ($burnup_period->getEndDate() >= $yesterday) {
                $this->logger->debug(
                    "Calculating burnup for artifact #" . $burnup['id'] . ' at ' . date('Y-m-d H:i:s', $yesterday)
                );

                $effort       = $this->burnup_calculator->getValue($burnup['id'], $yesterday);
                $team_effort  = $effort->getTeamEffort();
                $total_effort = $effort->getTotalEffort();

                $this->logger->debug("Caching value $team_effort/$total_effort for artifact #" . $burnup['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burnup['id'],
                    $yesterday,
                    $total_effort,
                    $team_effort
                );

                $subelements_cache_info = $this->burnup_count_elements_calculator->getValue(
                    $burnup['id'],
                    $yesterday
                );

                $closed_subelements = $subelements_cache_info->getClosedElements();
                $total_subelements  = $subelements_cache_info->getTotalElements();

                $this->logger->debug("Caching subelements value $closed_subelements/$total_subelements for artifact #" . $burnup['id']);
                $this->count_elements_cache_dao->saveCachedFieldValueAtTimestampForSubelements(
                    (int) $burnup['id'],
                    (int) $yesterday,
                    (int) $total_subelements,
                    (int) $closed_subelements
                );

                $this->logger->debug("End calculs for artifact #" . $burnup['id']);
            }
        }
    }
}
