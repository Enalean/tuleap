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
use Psr\Log\LoggerInterface;
use Tracker_Chart_Data_Burndown;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationFieldRetriever;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_GENERATE;

class BurndownCacheGenerationChecker
{
    /**
     * @var ChartCachedDaysComparator
     */
    private $cached_days_comparator;
    /**
     * @var ComputedFieldDao
     */
    private $computed_dao;
    /**
     * @var ChartConfigurationValueChecker
     */
    private $value_checker;
    /**
     * @var ChartConfigurationFieldRetriever
     */
    private $field_retriever;
    /**
     * @var BurndownCacheGenerator
     */
    private $cache_generator;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var \SystemEventManager
     */
    private $event_manager;
    /**
     * @var BurndownRemainingEffortAdderForREST
     */
    private $remaining_effort_adder;

    public function __construct(
        LoggerInterface $logger,
        BurndownCacheGenerator $cache_generator,
        \SystemEventManager $event_manager,
        ChartConfigurationFieldRetriever $field_retriever,
        ChartConfigurationValueChecker $value_checker,
        ComputedFieldDao $computed_dao,
        ChartCachedDaysComparator $cached_days_comparator,
        BurndownRemainingEffortAdderForREST $remaining_effort_adder,
    ) {
        $this->logger                 = $logger;
        $this->cache_generator        = $cache_generator;
        $this->event_manager          = $event_manager;
        $this->field_retriever        = $field_retriever;
        $this->value_checker          = $value_checker;
        $this->computed_dao           = $computed_dao;
        $this->cached_days_comparator = $cached_days_comparator;
        $this->remaining_effort_adder = $remaining_effort_adder;
    }

    public function isCacheBurndownAlreadyAsked(Artifact $artifact)
    {
        return $this->event_manager->areThereMultipleEventsQueuedMatchingFirstParameter(
            SystemEvent_BURNDOWN_GENERATE::class,
            $artifact->getId()
        );
    }

    public function isBurndownUnderCalculationBasedOnServerTimezone(
        Artifact $artifact,
        PFUser $user,
        DatePeriodWithoutWeekEnd $date_period,
        $capacity,
    ) {
        $start = $this->getDatePeriodStartDateAtMidnight($date_period);

        $this->logger->debug("Start date after updating timezone: " . $start->getTimestamp());

        $date_period_with_start_date_from_midnight = DatePeriodWithoutWeekEnd::buildFromDuration(
            $start->getTimestamp(),
            $date_period->getDuration()
        );

        $server_burndown_data = new Tracker_Chart_Data_Burndown($date_period_with_start_date_from_midnight, $capacity);

        $this->remaining_effort_adder->addRemainingEffortDataForREST($server_burndown_data, $artifact, $user);
        if (
            $this->isCacheCompleteForBurndown($date_period_with_start_date_from_midnight, $artifact, $user) === false
            && $this->isCacheBurndownAlreadyAsked($artifact) === false
        ) {
            $this->cache_generator->forceBurndownCacheGeneration($artifact->getId());
            $server_burndown_data->setIsBeingCalculated(true);
        } elseif ($this->isCacheBurndownAlreadyAsked($artifact)) {
            $server_burndown_data->setIsBeingCalculated(true);
        }

        return $server_burndown_data->isBeingCalculated();
    }

    private function isCacheCompleteForBurndown(
        DatePeriodWithoutWeekEnd $date_period,
        Artifact $artifact,
        PFUser $user,
    ) {
        if (
            $this->value_checker->doesUserCanReadRemainingEffort($artifact, $user) === true
            && $this->value_checker->hasStartDate($artifact, $user) === true
        ) {
            $cached_days = $this->computed_dao->getCachedDays(
                $artifact->getId(),
                $this->field_retriever->getBurndownRemainingEffortField($artifact, $user)->getId()
            );

            return $this->cached_days_comparator->isNumberOfCachedDaysExpected($date_period, $cached_days['cached_days'] ?? 0);
        }

        return true;
    }

    private function getDatePeriodStartDateAtMidnight(DatePeriodWithoutWeekEnd $date_period): DateTime
    {
        $start_date = $date_period->getStartDate();

        if ($start_date === null) {
            $start_date = $_SERVER['REQUEST_TIME'];
        }

        $start = new DateTime();
        $start->setTimestamp($start_date);
        $start->setTime(0, 0, 0);

        return $start;
    }
}
