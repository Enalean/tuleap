<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use PFUser;
use Psr\Log\LoggerInterface;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\Date\DatePeriod;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\ChartCachedDaysComparator;
use Tuleap\Tracker\FormElement\ChartConfigurationValueChecker;

class BurnupCacheChecker
{
    public function __construct(
        private readonly BurnupCacheGenerator $cache_generator,
        private readonly ChartConfigurationValueChecker $chart_value_checker,
        private readonly BurnupCacheDao $burnup_effort_cache_dao,
        private readonly CountElementsCacheDao $burnup_count_cache_dao,
        private readonly ChartCachedDaysComparator $cache_days_comparator,
        private readonly CountElementsModeChecker $mode_checker,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param int[] $expected_days
     */
    public function isBurnupUnderCalculation(Artifact $artifact, array $expected_days, PFUser $user, DatePeriod $date_period): bool
    {
        $is_burnup_under_calculation = false;

        $today = new \DateTimeImmutable('today');
        if ($date_period->getStartDate() > $today->getTimestamp()) {
            $this->logger->debug('Cache is always valid when start date is in future');
        } elseif ($date_period->getDuration() === 0) {
            $this->logger->debug('Cache is always valid when burnup has no duration');
        } elseif (! $this->isCacheCompleteForBurnup($artifact, $expected_days, $user)) {
            $this->logger->debug('Cache is not complete for burnup, force cache regeneration is asked');
            $this->cache_generator->forceBurnupCacheGeneration($artifact);
            $is_burnup_under_calculation = true;
        } elseif ($this->cache_generator->isCacheBurnupAlreadyAsked($artifact)) {
            $this->logger->debug('Cache is not complete for burnup, but event is already in queue');
            $is_burnup_under_calculation = true;
        }

        return $is_burnup_under_calculation;
    }

    /**
     * @param int[] $expected_days
     */
    private function isCacheCompleteForBurnup(
        Artifact $artifact,
        array $expected_days,
        PFUser $user,
    ): bool {
        if (! $this->chart_value_checker->hasStartDate($artifact, $user)) {
            return true;
        }

        return $this->cache_days_comparator->areCachedDaysCorrect(
            $expected_days,
            $this->getCachedDaysTimestamps($artifact),
        );
    }

    /**
     * @return list<int>
     */
    private function getCachedDaysTimestamps(Artifact $artifact): array
    {
        return $this->mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject())
            ? $this->burnup_count_cache_dao->getCachedDaysTimestamps($artifact->getId())
            : $this->burnup_effort_cache_dao->getCachedDaysTimestamps($artifact->getId());
    }
}
