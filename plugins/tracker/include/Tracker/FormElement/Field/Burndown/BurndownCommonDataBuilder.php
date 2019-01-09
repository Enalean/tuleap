<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use Logger;
use PFUser;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurndownCommonDataBuilder
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;

    /**
     * @var ChartConfigurationValueRetriever
     */
    private $value_retriever;

    /**
     * @var BurndownCacheGenerationChecker
     */
    private $cache_checker;

    public function __construct(
        Logger $logger,
        ChartConfigurationFieldRetriever $field_retriever,
        ChartConfigurationValueRetriever $value_retriever,
        BurndownCacheGenerationChecker $cache_checker
    ) {
        $this->logger          = $logger;
        $this->field_retriever = $field_retriever;
        $this->value_retriever = $value_retriever;
        $this->cache_checker   = $cache_checker;
    }

    /**
     * @return bool
     */
    public function getBurndownCalculationStatus(
        Tracker_Artifact $artifact,
        PFUser $user,
        $start_date,
        $duration,
        $capacity,
        $user_timezone
    ) {
        $this->logger->info("Start calculating burndown " . $artifact->getId());

        $server_timezone = TimezoneRetriever::getServerTimezone();

        date_default_timezone_set($server_timezone);

        $this->logger->debug("Capacity: " . $capacity);
        $this->logger->debug("Original start date: " . $start_date);
        $this->logger->debug("Duration: " . $duration);
        $this->logger->debug("User Timezone: " . $user_timezone);
        $this->logger->debug("Server timezone: " . $server_timezone);

        return $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
            $artifact,
            $user,
            $start_date,
            $duration,
            $capacity
        );
    }

    /**
     * @return int|null
     */
    public function getCapacity(Tracker_Artifact $artifact, PFUser $user)
    {
        $capacity = null;

        if ($this->field_retriever->doesCapacityFieldExist($artifact->getTracker())) {
            $capacity = $this->value_retriever->getCapacity($artifact, $user);
        }

        return $capacity;
    }

    /**
     * @param $start_date
     * @param $duration
     * @return TimePeriodWithoutWeekEnd
     */
    public function getTimePeriod($start_date, $duration)
    {
        if (! $start_date) {
            $start_date = $_SERVER['REQUEST_TIME'];
        }

        return new TimePeriodWithoutWeekEnd($start_date, $duration);
    }
}
