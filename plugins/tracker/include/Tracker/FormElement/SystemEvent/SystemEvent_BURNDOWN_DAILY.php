<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\SystemEvent;

use BackendLogger;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tracker_FormElement_Field_BurndownDao;
use Tracker_FormElement_Field_ComputedDaoCache;
use Tuleap\Tracker\FormElement\BurndownCalculator;
use Tuleap\Tracker\FormElement\BurndownDateRetriever;

class SystemEvent_BURNDOWN_DAILY extends SystemEvent
{
    const NAME = 'SystemEvent_BURNDOWN_DAILY';

    /**
     * @var Tracker_FormElement_Field_BurndownDao
     */
    private $burndown_dao;

    /**
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var  BurndownCalculator
     */
    private $burndown_calculator;

    /**
     * @var Tracker_FormElement_Field_ComputedDaoCache
     */
    private $cache_dao;

    /**
     * @var BurndownDateRetriever
     */
    private $date_retriever;

    public function verbalizeParameters($with_link)
    {
    }

    public function injectDependencies(
        Tracker_FormElement_Field_BurndownDao $burndown_dao,
        BurndownCalculator $burndown_calculator,
        Tracker_FormElement_Field_ComputedDaoCache $cache_dao,
        BackendLogger $logger,
        BurndownDateRetriever $date_retriever
    ) {
        $this->burndown_dao        = $burndown_dao;
        $this->logger              = $logger;
        $this->burndown_calculator = $burndown_calculator;
        $this->cache_dao           = $cache_dao;
        $this->date_retriever      = $date_retriever;
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

        foreach ($this->burndown_dao->getArtifactsWithBurndown() as $burndown) {
            $burndown_period = new TimePeriodWithoutWeekEnd($burndown['start_date'], $burndown['duration']);

            if ($burndown_period->getEndDate() >= $yesterday) {
                $this->logger->debug(
                    "Calculating burndown for artifact #" . $burndown['id'] . ' at ' . date('Y-m-d H:i:s', $yesterday)
                );

                $value = $this->burndown_calculator->calculateBurndownValueAtTimestamp($burndown, $yesterday);

                $this->logger->debug("Caching value $value for artifact #" . $burndown['id']);
                $this->cache_dao->saveCachedFieldValueAtTimestamp(
                    $burndown['id'],
                    $burndown['burndown_field_id'],
                    $yesterday,
                    $value
                );

                $this->logger->debug("End calculs for artifact #" . $burndown['id']);
            }
        }
    }
}
