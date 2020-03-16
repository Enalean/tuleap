<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use AgileDashboard_Milestone_MilestoneReportCriterionProvider;
use Tracker_Report_AdditionalCriterion;

class UnplannedReportCriterionChecker
{
    /** @var array */
    private $additional_criteria;

    public function __construct(array $additional_criteria)
    {
        $this->additional_criteria = $additional_criteria;
    }

    public function isUnplannedValueSelected(): bool
    {
        return $this->getValueId() !== null;
    }

    private function getValueId()
    {
        if (! isset($this->additional_criteria[AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME])) {
            return null;
        }

        $additional_criterion_value = $this->additional_criteria[AgileDashboard_Milestone_MilestoneReportCriterionProvider::FIELD_NAME];

        assert($additional_criterion_value instanceof Tracker_Report_AdditionalCriterion);

        return $additional_criterion_value->getValue() == AgileDashboard_Milestone_MilestoneReportCriterionProvider::UNPLANNED ?: null;
    }
}
