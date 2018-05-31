<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use BackendLogger;
use DateTime;
use SystemEvent;
use TimePeriodWithoutWeekEnd;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDao;
use Tuleap\AgileDashboard\FormElement\BurnupCacheDateRetriever;
use Tuleap\AgileDashboard\FormElement\BurnupCalculator;
use Tuleap\AgileDashboard\FormElement\BurnupDao;

class SystemEvent_BURNUP_GENERATE extends SystemEvent // @codingStandardsIgnoreLine
{
    /**
     * @var BurnupCalculator
     */
    public $burnup_calculator;

    /**
     * @var BurnupDao
     */
    private $burnup_dao;

    /**
     * @var BackendLogger
     */
    private $logger;

    /**
     * @var BurnupCacheDao
     */
    private $cache_dao;

    /**
     * @var BurnupCacheDateRetriever
     */
    private $date_retriever;

    public function injectDependencies(
        BurnupDao $burnup_dao,
        BurnupCalculator $burnup_calculator,
        BurnupCacheDao $cache_dao,
        BackendLogger $logger,
        BurnupCacheDateRetriever $date_retriever
    ) {
        $this->burnup_dao        = $burnup_dao;
        $this->logger            = $logger;
        $this->burnup_calculator = $burnup_calculator;
        $this->cache_dao         = $cache_dao;
        $this->date_retriever    = $date_retriever;
    }

    private function getArtifactIdFromParameters()
    {
        $parameters = $this->getParametersAsArray();

        return $parameters[0];
    }

    public function verbalizeParameters($with_link)
    {
        return 'Artifact_id : ' . $this->getArtifactIdFromParameters();
    }

    public function process()
    {
        $artifact_id        = $this->getArtifactIdFromParameters();
        $burnup_information = $this->burnup_dao->getBurnupInformation($artifact_id);

        $this->logger->debug("Calculating burnup for artifact #" . $artifact_id);
        if (! $burnup_information) {
            $warning = "Can't generate cache for artifact #" . $artifact_id . ". Please check your burnup configuration";
            $this->warning($warning);
            $this->logger->debug($warning);

            return false;
        }

        $burnup_period = new TimePeriodWithoutWeekEnd(
            $burnup_information['start_date'],
            $burnup_information['duration']
        );

        $yesterday = new DateTime();
        $yesterday->setTime(23, 59, 59);

        $this->cache_dao->deleteArtifactCacheValue(
            $burnup_information['id']
        );

        foreach ($this->date_retriever->getWorkedDaysToCacheForPeriod($burnup_period, $yesterday) as $worked_day) {
            $this->logger->debug("Day " . date("Y-m-d H:i:s", $worked_day));

            $effort       = $this->burnup_calculator->getValue($burnup_information['id'], $worked_day);
            $team_effort  = $effort->getTeamEffort();
            $total_effort = $effort->getTotalEffort();

            $this->logger->debug("Caching value $team_effort/$total_effort for artifact #" . $burnup_information['id']);
            $this->cache_dao->saveCachedFieldValueAtTimestamp(
                $burnup_information['id'],
                $worked_day,
                $total_effort,
                $team_effort
            );
        }

        $this->logger->debug("End calculs for artifact #" . $artifact_id);
        $this->done();

        return true;
    }
}
