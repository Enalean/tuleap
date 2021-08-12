<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\Report;

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_Report;

class ReportCriteriaJsonBuilder
{
    public function buildReportCriteriaJson(Tracker_Report $report): ReportCriteriaJson
    {
        if ($report->is_in_expert_mode) {
            return $this->buildExpertReportCriteriaJson($report);
        }

        return $this->buildClassicReportCriteriaJson($report);
    }

    private function buildExpertReportCriteriaJson(Tracker_Report $report): ExpertReportCriteriaJson
    {
        return new ExpertReportCriteriaJson(
            $report->expert_query
        );
    }

    private function buildClassicReportCriteriaJson(Tracker_Report $report): ClassicReportCriteriaJson
    {
        $criteria_value_json = [];
        foreach ($report->getCriteria() as $criterion) {
            $criterion_field = $criterion->getField();
            if (
                $criterion_field instanceof Tracker_FormElement_Field_List ||
                $criterion_field instanceof Tracker_FormElement_Field_Date
            ) {
                continue;
            }

            if (! empty($criterion_field->getCriteriaValue($criterion))) {
                $criteria_value_json[] = new CriterionValueJson(
                    $criterion_field->getLabel(),
                    (string) $criterion_field->getCriteriaValue($criterion),
                );
            }
        }

        return new ClassicReportCriteriaJson(
            $criteria_value_json
        );
    }
}
