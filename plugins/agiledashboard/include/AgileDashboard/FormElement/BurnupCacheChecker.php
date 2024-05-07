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
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsCacheDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
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
    ) {
    }

    public function isBurnupUnderCalculation(Artifact $artifact, DatePeriodWithoutWeekEnd $date_period, PFUser $user): bool
    {
        $is_burnup_under_calculation = false;

        if (! $this->isCacheCompleteForBurnup($artifact, $date_period, $user)) {
            $this->cache_generator->forceBurnupCacheGeneration($artifact);
            $is_burnup_under_calculation = true;
        } elseif ($this->cache_generator->isCacheBurnupAlreadyAsked($artifact)) {
            $is_burnup_under_calculation = true;
        }

        return $is_burnup_under_calculation;
    }

    private function isCacheCompleteForBurnup(
        Artifact $artifact,
        DatePeriodWithoutWeekEnd $date_period,
        PFUser $user,
    ): bool {
        if (! $this->chart_value_checker->hasStartDate($artifact, $user)) {
            return true;
        }

        return $this->cache_days_comparator->isNumberOfCachedDaysExpected(
            $date_period,
            $this->getNumberOfCachedDays($artifact),
        );
    }

    private function getNumberOfCachedDays(Artifact $artifact): int
    {
        $is_in_count_elements_mode = $this->mode_checker->burnupMustUseCountElementsMode($artifact->getTracker()->getProject());

        return $is_in_count_elements_mode
            ? $this->burnup_count_cache_dao->getNumberOfCachedDays($artifact->getId())
            : $this->burnup_effort_cache_dao->getNumberOfCachedDays($artifact->getId());
    }
}
