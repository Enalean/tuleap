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

use DateTime;
use Logger;
use PFUser;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact;
use Tracker_Chart_Data_Burndown;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurndownDataBuilder
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
    /**
     * @var BurndownRemainingEffortAdder
     */
    private $reminaing_effort_adder;

    public function __construct(
        Logger $logger,
        ChartConfigurationFieldRetriever $field_retriever,
        ChartConfigurationValueRetriever $value_retriever,
        BurndownCacheGenerationChecker $cache_checker,
        BurndownRemainingEffortAdder $remaining_effort_adder
    ) {
        $this->logger                 = $logger;
        $this->field_retriever        = $field_retriever;
        $this->value_retriever        = $value_retriever;
        $this->cache_checker          = $cache_checker;
        $this->reminaing_effort_adder = $remaining_effort_adder;
    }

    public function build(Tracker_Artifact $artifact, PFUser $user, $start_date, $duration)
    {
        $this->logger->info("Start calculating burndown " . $artifact->getId());

        $capacity = null;
        if ($this->field_retriever->doesCapacityFieldExist($artifact->getTracker())) {
            $capacity = $this->value_retriever->getCapacity($artifact, $user);
        }

        $user_timezone   = TimezoneRetriever::getUserTimezone($user);
        $server_timezone = TimezoneRetriever::getServerTimezone();

        date_default_timezone_set($server_timezone);

        $this->logger->debug("Capacity: " . $capacity);
        $this->logger->debug("Original start date: " . $start_date);
        $this->logger->debug("Duration: " . $duration);
        $this->logger->debug("User Timezone: " . $user_timezone);
        $this->logger->debug("Server timezone: " . $server_timezone);

        $is_burndown_under_calculation = $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
            $artifact,
            $user,
            $start_date,
            $duration,
            $capacity
        );

        $efforts = $this->addBurndownRemainingEffortDotsBasedOnServerTimezone(
            $artifact,
            $user,
            $start_date,
            $duration,
            $capacity,
            $is_burndown_under_calculation
        );

        $this->logger->info("End calculating burndown " . $artifact->getId());


        date_default_timezone_set($user_timezone);

        return $efforts;
    }

    private function addBurndownRemainingEffortDotsBasedOnServerTimezone(
        Tracker_Artifact $artifact,
        PFUser $user,
        $start_date,
        $duration,
        $capacity,
        $is_burndown_under_calculation
    ) {
        if (! $start_date) {
            $start_date = $_SERVER['REQUEST_TIME'];
        }

        $start = new  DateTime();
        $start->setTimestamp($start_date);
        $start->setTime(0, 0, 0);

        $user_time_period   = new TimePeriodWithoutWeekEnd($start_date, $duration);
        $user_burndown_data = new Tracker_Chart_Data_Burndown($user_time_period, $capacity);

        if ($is_burndown_under_calculation === false) {
            $this->reminaing_effort_adder->addRemainingEffortData(
                $user_burndown_data,
                $user_time_period,
                $artifact,
                $user
            );
        }

        $user_burndown_data->setIsBeingCalculated($is_burndown_under_calculation);

        return $user_burndown_data;
    }
}
