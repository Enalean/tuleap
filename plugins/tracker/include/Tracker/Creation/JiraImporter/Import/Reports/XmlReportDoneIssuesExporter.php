<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\JiraFieldAPIAllowedValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Report\XML\XMLReport;

class XmlReportDoneIssuesExporter implements IExportJiraLikeXmlReport
{
    /**
     * @var XmlReportDefaultCriteriaExporter
     */
    private $default_criteria_exporter;
    /**
     * @var XmlReportTableExporter
     */
    private $report_table_exporter;

    public function __construct(
        XmlReportDefaultCriteriaExporter $default_criteria_exporter,
        XmlReportTableExporter $report_table_exporter,
    ) {
        $this->default_criteria_exporter = $default_criteria_exporter;
        $this->report_table_exporter     = $report_table_exporter;
    }

    public function exportJiraLikeReport(
        SimpleXMLElement $reports_node,
        StatusValuesCollection $status_values_collection,
        ?FieldMapping $summary_field,
        ?FieldMapping $description_field,
        ?FieldMapping $status_field,
        ?FieldMapping $priority_field,
        ?FieldMapping $link_field,
        ?FieldMapping $created_field,
        ?FieldMapping $updated_field,
        ?FieldMapping $issue_type_field,
    ): void {
        if (! $status_field) {
            return;
        }

        $done_values = $status_values_collection->getClosedValues();

        if (count($done_values) === 0) {
            return;
        }

        $report_node = (new XMLReport('Done issues'))
            ->withDescription('All done issues in this tracker')
            ->export($reports_node, new XMLFormElementFlattenedCollection([]));

        $criteria_fields = array_filter(
            [
                $summary_field,
                $description_field,
                $priority_field,
                $issue_type_field,
            ]
        );

        $this->exportDoneIssuesCriteria(
            $report_node->criterias,
            $criteria_fields,
            $status_field,
            $done_values
        );

        $column_fields = array_filter(
            [
                $summary_field,
                $status_field,
                $link_field,
                $priority_field,
                $issue_type_field,
            ]
        );

        $this->report_table_exporter->exportResultsTable(
            $report_node->renderers,
            $column_fields
        );
    }

    /**
     * @param FieldMapping[] $field_mappings
     * @param JiraFieldAPIAllowedValueRepresentation[] $done_values
     */
    private function exportDoneIssuesCriteria(
        SimpleXMLElement $criterias_node,
        array $field_mappings,
        FieldMapping $status_field,
        array $done_values,
    ): void {
        $criteria_node = $criterias_node->addChild('criteria');

        $criteria_node->addAttribute('rank', '0');
        $criteria_node->addAttribute('is_advanced', '1');

        $criteria_field_node = $criteria_node->addChild('field');

        $criteria_field_node->addAttribute("REF", $status_field->getXMLId());

        $criteria_value = $criteria_node->addChild('criteria_value');

        $criteria_value->addAttribute('type', 'list');

        foreach ($done_values as $done_value) {
            $selected_value = $criteria_value->addChild('selected_value');
            $selected_value->addAttribute('REF', $done_value->getXMLId());
        }

        $this->default_criteria_exporter->exportDefaultCriteria($field_mappings, $criterias_node);
    }
}
