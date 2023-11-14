<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use PFUser;
use Psr\Log\LoggerInterface;
use Tracker_Chart_Data_Burndown;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;

class BurndownDataBuilderForREST
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var BurndownRemainingEffortAdderForREST
     */
    private $remaining_effort_adder;

    /**
     * @var BurndownCommonDataBuilder
     */
    private $common_data_builder;

    public function __construct(
        LoggerInterface $logger,
        BurndownRemainingEffortAdderForREST $remaining_effort_adder,
        BurndownCommonDataBuilder $common_data_builder,
    ) {
        $this->logger                 = $logger;
        $this->remaining_effort_adder = $remaining_effort_adder;
        $this->common_data_builder    = $common_data_builder;
    }

    public function build(Artifact $artifact, PFUser $user, DatePeriodWithoutWeekEnd $date_period)
    {
        $capacity      = $this->common_data_builder->getCapacity($artifact, $user);
        $user_timezone = TimezoneRetriever::getUserTimezone($user);

        $is_burndown_under_calculation = $this->common_data_builder->getBurndownCalculationStatus(
            $artifact,
            $user,
            $date_period,
            $capacity,
            $user_timezone
        );

        $efforts = $this->addBurndownRemainingEffortDotsBasedOnServerTimezoneForREST(
            $artifact,
            $user,
            $date_period,
            $capacity,
            $is_burndown_under_calculation
        );

        $this->logger->info("End calculating burndown " . $artifact->getId());

        date_default_timezone_set($user_timezone);

        return $efforts;
    }

    private function addBurndownRemainingEffortDotsBasedOnServerTimezoneForREST(
        Artifact $artifact,
        PFUser $user,
        DatePeriodWithoutWeekEnd $date_period,
        $capacity,
        $is_burndown_under_calculation,
    ) {
        $user_time_period   = $this->common_data_builder->getDatePeriod($date_period);
        $user_burndown_data = new Tracker_Chart_Data_Burndown($user_time_period, $capacity);

        if ($is_burndown_under_calculation === false) {
            $this->remaining_effort_adder->addRemainingEffortDataForREST(
                $user_burndown_data,
                $artifact,
                $user
            );
        }

        $user_burndown_data->setIsBeingCalculated($is_burndown_under_calculation);

        return $user_burndown_data;
    }
}
