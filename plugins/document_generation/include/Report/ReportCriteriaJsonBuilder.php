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

use Tracker_FormElement_InvalidFieldValueException;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\FormElement\Field\ListField;

class ReportCriteriaJsonBuilder
{
    public function __construct(private \UGroupManager $ugroup_manager)
    {
    }

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
            if ($criterion_field instanceof DateField) {
                $date_criterion_json_value = $this->buildCriterionValueJsonFromDateValue($criterion);
                if ($date_criterion_json_value !== null) {
                    $criteria_value_json[] = $date_criterion_json_value;
                }
            } elseif ($criterion_field instanceof OpenListField) {
                $open_list_criterion_json_value = $this->buildCriterionValueJsonFromOpenListValue($criterion);
                if ($open_list_criterion_json_value !== null) {
                    $criteria_value_json[] = $open_list_criterion_json_value;
                }
            } elseif ($criterion_field instanceof ListField) {
                $list_criterion_json_value = $this->buildCriterionValueJsonFromListValue($criterion);
                if ($list_criterion_json_value !== null) {
                    $criteria_value_json[] = $list_criterion_json_value;
                }
            } elseif ($criterion_field instanceof \Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactField) {
                $value = $this->buildCriterionValueJsonFromPermissionsOnArtifactValue($criterion);
                if ($value !== null) {
                    $criteria_value_json[] = $value;
                }
            } elseif (! empty($criterion_field->getCriteriaValue($criterion))) {
                $criteria_value_json[] = new ClassicCriterionValueJson(
                    $criterion_field->getLabel(),
                    (string) $criterion_field->getCriteriaValue($criterion),
                );
            }
        }

        foreach ($report->getAdditionalCriteria(false) as $additional_criterion) {
            $criteria_value = (string) $additional_criterion->getValue();
            if ($criteria_value !== '') {
                $criteria_value_json[] = new ClassicCriterionValueJson(
                    $additional_criterion->getKey(),
                    $criteria_value,
                );
            }
        }

        return new ClassicReportCriteriaJson(
            $criteria_value_json
        );
    }

    private function buildCriterionValueJsonFromDateValue(Tracker_Report_Criteria $criterion): ?DateCriterionValueJson
    {
        $criterion_field = $criterion->getField();
        $criterion_value = $criterion_field->getCriteriaValue($criterion);

        if (
            ! is_array($criterion_value) ||
            ! array_key_exists('from_date', $criterion_value) ||
            ! array_key_exists('to_date', $criterion_value)
        ) {
            return null;
        }

        return new DateCriterionValueJson(
            $criterion_field->getLabel(),
            (int) $criterion_value['from_date'] !== 0 ? JsonCast::toDate($criterion_value['from_date']) : null,
            (int) $criterion_value['to_date'] !== 0 ? JsonCast::toDate($criterion_value['to_date']) : null,
            $criterion_value['op'],
            (bool) $criterion->is_advanced
        );
    }

    private function buildCriterionValueJsonFromOpenListValue(Tracker_Report_Criteria $criterion): ?CriterionValueJson
    {
        $criterion_field = $criterion->getField();
        $criterion_value = $criterion_field->getCriteriaValue($criterion);

        if (! is_string($criterion_value) || $criterion_value === '') {
            return null;
        }

        $selected_open_list_values    = $criterion_field->extractCriteriaValue($criterion_value);
        $criterion_open_values_labels = [];
        foreach ($selected_open_list_values as $value) {
            if ($value) {
                $criterion_open_values_labels[] = $value->getLabel();
            }
        }

        if (count($criterion_open_values_labels) > 0) {
            return new ClassicCriterionValueJson(
                $criterion_field->getLabel(),
                implode(', ', $criterion_open_values_labels),
            );
        }

        return null;
    }

    private function buildCriterionValueJsonFromListValue(Tracker_Report_Criteria $criterion): ?CriterionValueJson
    {
        $criterion_field = $criterion->getField();
        $criterion_value = $criterion_field->getCriteriaValue($criterion);

        if (! is_array($criterion_value)) {
            return null;
        }

        $criterion_values_labels = [];
        foreach ($criterion_value as $value_id) {
            if ((int) $value_id === ListField::NONE_VALUE) {
                $criterion_values_labels[] = $GLOBALS['Language']->getText('global', 'none');
            } else {
                try {
                    $value = $criterion_field->getBind()->getValue($value_id);
                } catch (Tracker_FormElement_InvalidFieldValueException $ex) {
                    continue;
                }
                if ($value) {
                    $criterion_values_labels[] = $value->getLabel();
                }
            }
        }

        if (count($criterion_values_labels) > 0) {
            return new ClassicCriterionValueJson(
                $criterion_field->getLabel(),
                implode(', ', $criterion_values_labels),
            );
        }

        return null;
    }

    private function buildCriterionValueJsonFromPermissionsOnArtifactValue(Tracker_Report_Criteria $criterion): ?CriterionValueJson
    {
        $criterion_field = $criterion->getField();
        $criterion_value = $criterion_field->getCriteriaValue($criterion);

        if (! is_array($criterion_value)) {
            return null;
        }

        $project = $criterion_field->getTracker()->getProject();

        $labels = [];

        foreach (array_keys($criterion_value) as $ugroup_id) {
            $ugroup = $this->ugroup_manager->getUGroup($project, $ugroup_id);
            if ($ugroup !== null) {
                $labels[] = $ugroup->getTranslatedName();
            }
        }

        if (count($labels) > 0) {
            return new ClassicCriterionValueJson(
                $criterion_field->getLabel(),
                implode(', ', $labels),
            );
        }

        return null;
    }
}
