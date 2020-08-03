<?php
/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning\Admin;

use Tracker;
use Tuleap\Event\Dispatchable;

final class AdditionalPlanningConfigurationWarningsRetriever implements Dispatchable
{
    public const NAME = 'additionalPlanningConfigurationWarningsRetriever';

    /**
     * @var PlanningWarningPossibleMisconfigurationPresenter[]
     */
    private $warnings = [];

    /**
     * @var Tracker
     * @psalm-readonly
     */
    private $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    /**
     * @psalm-mutation-free
     */
    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function addWarning(PlanningWarningPossibleMisconfigurationPresenter $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * @return PlanningWarningPossibleMisconfigurationPresenter[]
     * @psalm-mutation-free
     */
    public function getAllWarnings(): array
    {
        return $this->warnings;
    }
}
