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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Burndown;

use DateTime;
use PFUser;
use Psr\Log\LoggerInterface;
use SystemEventManager;
use Tracker_Chart_Data_Burndown;
use Tuleap\Date\DatePeriodWithOpenDays;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\BurndownCacheDateRetriever;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_GENERATE;

class BurndownCacheGenerationChecker
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BurndownCacheGenerator $cache_generator,
        private readonly SystemEventManager $event_manager,
        private readonly ChartConfigurationFieldRetriever $field_retriever,
        private readonly ChartConfigurationValueChecker $value_checker,
        private readonly ComputedFieldDao $computed_dao,
        private readonly ChartCachedDaysComparator $cached_days_comparator,
        private readonly BurndownRemainingEffortAdderForREST $remaining_effort_adder,
        private readonly BurndownCacheDateRetriever $date_retriever,
    ) {
    }

    public function isCacheBurndownAlreadyAsked(Artifact $artifact): bool
    {
        return $this->event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
            SystemEvent_BURNDOWN_GENERATE::class,
            $artifact->getId()
        );
    }

    public function isBurndownUnderCalculationBasedOnServerTimezone(
        Artifact $artifact,
        PFUser $user,
        DatePeriodWithOpenDays $date_period,
        $capacity,
    ): bool {
        $this->logger->debug('Burndown start date: ' . (string) $date_period->getStartDate());

        $date_period_with_start_date_from_midnight = DatePeriodWithOpenDays::buildFromDuration(
            $date_period->getStartDate(),
            $date_period->getDuration()
        );

        $server_burndown_data = new Tracker_Chart_Data_Burndown($date_period_with_start_date_from_midnight, $capacity);
        $worked_days          = $this->date_retriever->getWorkedDaysToCacheForPeriod($date_period_with_start_date_from_midnight, new DateTime('yesterday'));

        $this->remaining_effort_adder->addRemainingEffortDataForREST($server_burndown_data, $artifact, $user);
        if (
            $this->isCacheCompleteForBurndown($worked_days, $artifact, $user) === false
            && $this->isCacheBurndownAlreadyAsked($artifact) === false
        ) {
            $this->cache_generator->forceBurndownCacheGeneration($artifact->getId());
            $server_burndown_data->setIsBeingCalculated(true);
        } elseif ($this->isCacheBurndownAlreadyAsked($artifact)) {
            $server_burndown_data->setIsBeingCalculated(true);
        }

        return $server_burndown_data->isBeingCalculated();
    }

    /**
     * @param int[] $expected_days
     */
    private function isCacheCompleteForBurndown(
        array $expected_days,
        Artifact $artifact,
        PFUser $user,
    ): bool {
        if (
            $this->value_checker->doesUserCanReadRemainingEffort($artifact, $user) === true
            && $this->value_checker->hasStartDate($artifact, $user) === true
        ) {
            $cached_days = $this->computed_dao->getCachedDays(
                $artifact->getId(),
                $this->field_retriever->getBurndownRemainingEffortField($artifact, $user)->getId()
            );
            $timestamps  = [];
            foreach ($cached_days as $cached_day) {
                $timestamps[] = (int) $cached_day['timestamp'];
            }

            return $this->cached_days_comparator->areCachedDaysCorrect($expected_days, $timestamps);
        }

        return true;
    }
}
