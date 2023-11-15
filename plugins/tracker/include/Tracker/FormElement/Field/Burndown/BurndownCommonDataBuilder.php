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
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

class BurndownCommonDataBuilder
{
    /**
     * @var LoggerInterface
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
        LoggerInterface $logger,
        ChartConfigurationFieldRetriever $field_retriever,
        ChartConfigurationValueRetriever $value_retriever,
        BurndownCacheGenerationChecker $cache_checker,
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
        Artifact $artifact,
        PFUser $user,
        DatePeriodWithoutWeekEnd $date_period,
        $capacity,
        $user_timezone,
    ) {
        $this->logger->info("Start calculating burndown " . $artifact->getId());

        $server_timezone = TimezoneRetriever::getServerTimezone();

        date_default_timezone_set($server_timezone);

        $this->logger->debug("Capacity: " . $capacity);
        $this->logger->debug("Original start date: " . (string) $date_period->getStartDate());
        $this->logger->debug("Duration: " . (string) $date_period->getDuration());
        $this->logger->debug("User Timezone: " . $user_timezone);
        $this->logger->debug("Server timezone: " . $server_timezone);

        return $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
            $artifact,
            $user,
            $date_period,
            $capacity
        );
    }

    /**
     * @return int|null
     */
    public function getCapacity(Artifact $artifact, PFUser $user)
    {
        $capacity = null;

        if ($this->field_retriever->doesCapacityFieldExist($artifact->getTracker())) {
            $capacity = $this->value_retriever->getCapacity($artifact, $user);
        }

        return $capacity;
    }

    public function getDatePeriod(DatePeriodWithoutWeekEnd $date_period): DatePeriodWithoutWeekEnd
    {
        if ($date_period->getStartDate() === null) {
            return DatePeriodWithoutWeekEnd::buildFromDuration($_SERVER['REQUEST_TIME'], $date_period->getDuration());
        }

        return $date_period;
    }
}
