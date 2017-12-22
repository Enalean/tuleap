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
use TimePeriod;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;

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

    public function __construct(
        Logger $logger,
        BurnupCacheChecker $cache_checker,
        ChartConfigurationValueRetriever $field_retriever
    ) {
        $this->logger          = $logger;
        $this->cache_checker   = $cache_checker;
        $this->field_retriever = $field_retriever;
    }

    public function buildBurnupData($artifact, \PFUser $user)
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

    private function getBurnupData(Tracker_Artifact $artifact, TimePeriod $time_period, \PFUser $user)
    {
        $user_timezone   = date_default_timezone_get();
        $server_timezone = TimezoneRetriever::getServerTimezone();
        date_default_timezone_set($server_timezone);

        $start = new  DateTime();
        $start->setTimestamp($time_period->getStartDate());
        $start->setTime(0, 0, 0);

        $this->logger->debug("Start date after updating timezone: " . $start->getTimestamp());

        $time_period          = new TimePeriodWithoutWeekEnd($start->getTimestamp(), $time_period->getDuration());
        $burnup_data          = new BurnupData($time_period, false);
        $is_under_calculation = $this->cache_checker->isBurnupUnderCalculation($artifact, $burnup_data, $user);

        $this->logger->info("End calculating burnup " . $artifact->getId());
        date_default_timezone_set($user_timezone);

        $burnup_data->setIsBeingCalculated($is_under_calculation);

        return $burnup_data;
    }
}
