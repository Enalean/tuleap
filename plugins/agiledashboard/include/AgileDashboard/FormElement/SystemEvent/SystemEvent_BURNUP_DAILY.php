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

namespace Tuleap\Agiledashboard\FormElement\SystemEvent;

use BackendLogger;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tuleap\Agiledashboard\FormElement\BurnupCacheDao;
use Tuleap\Agiledashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupDao;
use Tuleap\Tracker\FormElement\FieldCalculator;

class SystemEvent_BURNUP_DAILY extends SystemEvent
{
    const NAME = 'SystemEvent_BURNUP_DAILY';

    /**
     * @var BurnupDao
     */
    private $burnup_dao;

    /**
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var  FieldCalculator
     */
    private $total_effort_calculator;

    /**
     * @var FieldCalculator
     */
    private $team_effort_calculator;

    /**
     * @var BurnupCacheDao
     */
    private $cache_dao;

    /**
     * @var BurnupCacheDateRetriever
     */
    private $date_retriever;

    public function verbalizeParameters($with_link)
    {
        return '-';
    }

    public function injectDependencies(
        BurnupDao $burnup_dao,
        FieldCalculator $total_effort_calculator,
        FieldCalculator $team_effort_calculator,
        BurnupCacheDao $cache_dao,
        BackendLogger $logger,
        BurnupCacheDateRetriever $date_retriever
    ) {
        $this->burnup_dao              = $burnup_dao;
        $this->logger                  = $logger;
        $this->total_effort_calculator = $total_effort_calculator;
        $this->team_effort_calculator  = $team_effort_calculator;
        $this->cache_dao               = $cache_dao;
        $this->date_retriever          = $date_retriever;
    }

    public function process()
    {
        $this->cacheYesterdayValues();
        $this->done();

        return true;
    }

    public function cacheYesterdayValues()
    {
        $yesterday = $this->date_retriever->getYesterday();

        $yesterday_period = new TimePeriodWithoutWeekEnd($yesterday, 1);
        if (! $yesterday_period->isNotWeekendDay($yesterday)) {
            return;
        }

        foreach ($this->burnup_dao->getArtifactsWithBurnup() as $burnup) {
            $burnup_period = new TimePeriodWithoutWeekEnd($burnup['start_date'], $burnup['duration']);

            if ($burnup_period->getEndDate() >= $yesterday) {
                $this->logger->debug(
                    "Calculating burnup for artifact #" . $burnup['id'] . ' at ' . date('Y-m-d H:i:s', $yesterday)
                );

                $total_effort = $this->total_effort_calculator->calculate(
                    array($burnup['id']),
                    $yesterday,
                    true,
                    null,
                    null
                );

                $team_effort = $this->team_effort_calculator->calculate(
                    array($burnup['id']),
                    $yesterday,
                    true,
                    null,
                    null
                );

                $this->logger->debug("Caching value $team_effort/$total_effort for artifact #" . $burnup['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burnup['id'],
                    $yesterday,
                    $total_effort,
                    $team_effort
                );

                $this->logger->debug("End calculs for artifact #" . $burnup['id']);
            }
        }
    }
}
