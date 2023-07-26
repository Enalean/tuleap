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
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Report\XML\XMLReport;

class XmlReportAllIssuesExporter implements IExportJiraLikeXmlReport
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
        $report_node = (new XMLReport('All issues'))
            ->withDescription('All the issues in this tracker')
            ->withIsDefault(true)
            ->export($reports_node, new XMLFormElementFlattenedCollection([]));

        $criteria_fields = array_filter(
            [
                $summary_field,
                $description_field,
                $status_field,
                $priority_field,
                $issue_type_field,
            ]
        );

        $column_fields = array_filter([
            $summary_field,
            $status_field,
            $link_field,
            $priority_field,
            $issue_type_field,
        ]);

        $this->default_criteria_exporter->exportDefaultCriteria(
            $criteria_fields,
            $report_node->criterias
        );

        $this->report_table_exporter->exportResultsTable($report_node->renderers, $column_fields);
    }
}
