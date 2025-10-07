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
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueRetriever;

readonly final class BurndownCommonDataBuilder
{
    public function __construct(
        private LoggerInterface $logger,
        private ChartConfigurationFieldRetriever $field_retriever,
        private ChartConfigurationValueRetriever $value_retriever,
        private BurndownCacheGenerationChecker $cache_checker,
    ) {
    }

    public function getBurndownCalculationStatus(
        Artifact $artifact,
        PFUser $user,
        DatePeriodWithOpenDays $date_period,
        $capacity,
        $user_timezone,
    ): bool {
        $this->logger->info('Start calculating burndown ' . $artifact->getId());

        $today = new \DateTimeImmutable('today');
        if ($date_period->getStartDate() > $today->getTimestamp()) {
            $this->logger->debug('Cache is always valid when start date is in future');
            return false;
        }

        if ($date_period->getDuration() === 0) {
            $this->logger->debug('Cache is always valid when burndown has no duration');
            return false;
        }

        $server_timezone = TimezoneRetriever::getServerTimezone();

        date_default_timezone_set($server_timezone);

        $this->logger->debug('Capacity: ' . $capacity);
        $this->logger->debug('Original start date: ' . (string) $date_period->getStartDate());
        $this->logger->debug('Duration: ' . (string) $date_period->getDuration());
        $this->logger->debug('User Timezone: ' . $user_timezone);
        $this->logger->debug('Server timezone: ' . $server_timezone);

        return $this->cache_checker->isBurndownUnderCalculationBasedOnServerTimezone(
            $artifact,
            $user,
            $date_period,
            $capacity
        );
    }

    public function getCapacity(Artifact $artifact, PFUser $user): ?int
    {
        $capacity = null;

        if ($this->field_retriever->doesCapacityFieldExist($artifact->getTracker())) {
            $capacity = $this->value_retriever->getCapacity($artifact, $user);
        }

        return $capacity;
    }

    public function getDatePeriod(DatePeriodWithOpenDays $date_period): DatePeriodWithOpenDays
    {
        if ($date_period->getStartDate() === null) {
            return DatePeriodWithOpenDays::buildFromDuration($_SERVER['REQUEST_TIME'], $date_period->getDuration());
        }

        return $date_period;
    }
}
