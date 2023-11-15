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

use DateTime;
use PFUser;
use Tracker_Chart_Data_Burndown;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\UserWithReadAllPermissionBuilder;

class BurndownRemainingEffortAdderForLegacy
{
    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;

    /**
     * @var UserWithReadAllPermissionBuilder
     */
    private $user_with_read_all_permission_builder;

    public function __construct(
        ChartConfigurationFieldRetriever $field_retriever,
        UserWithReadAllPermissionBuilder $user_with_read_all_permission_builder,
    ) {
        $this->field_retriever                       = $field_retriever;
        $this->user_with_read_all_permission_builder = $user_with_read_all_permission_builder;
    }

    public function addRemainingEffortDataForLegacy(
        Tracker_Chart_Data_Burndown $burndown_data,
        Artifact $artifact,
        PFUser $user,
    ) {
        $field = $this->field_retriever->getBurndownRemainingEffortField($artifact, $user);

        if (! $field) {
            return;
        }

        $date_period = $burndown_data->getDatePeriod();

        $date = $this->getFirstDayDate($date_period);
        $now  = new DateTime();

        if ($date_period->getStartDate() > $now->getTimestamp()) {
            return;
        }

        $offset_days = 0;

        while ($offset_days <= $date_period->getDuration()) {
            if ($date >= $now) {
                break;
            }

            $remaining_effort = $field->getCachedValue(
                $this->user_with_read_all_permission_builder->buildUserWithReadAllPermission($user),
                $artifact,
                $date->getTimestamp()
            );

            if ($remaining_effort !== false) {
                $date_midnight = $date;
                $date_midnight->setTime(0, 0, 0);

                $burndown_data->addEffortAt($offset_days, $remaining_effort);
                $burndown_data->addEffortAtDateTime($this->getMidnightDate($date), $remaining_effort);
                $offset_days++;
            }

            $date = $this->setTomorrow($date);
        }
    }

    /**
     * @return DateTime
     */
    private function getFirstDayDate(DatePeriodWithoutWeekEnd $time_period)
    {
        $date = new DateTime();
        $date->setTimestamp((int) $time_period->getStartDate());
        $date->setTime(23, 59, 59);

        return $date;
    }

    /**
     * @return DateTime
     */
    private function getMidnightDate(DateTime $date)
    {
        $date->setTime(0, 0, 0);

        return $date;
    }

    /**
     * @return DateTime
     */
    private function setTomorrow(DateTime $date)
    {
        $date->modify('+1 day');
        $date->setTime(23, 59, 59);

        return $date;
    }
}
