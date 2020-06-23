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
use XML_SimpleXMLCDATAFactory;

class XmlReportDoneIssuesExporter implements IExportJiraLikeXmlReport
{
    /**
     * @var XmlReportDefaultCriteriaExporter
     */
    private $default_criteria_exporter;

    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    /**
     * @var XmlReportTableExporter
     */
    private $report_table_exporter;

    /**
     * @var StatusValuesCollection
     */
    private $status_values_collection;

    /**
     * @param FieldMapping[] $column_fields
     */
    public function __construct(
        XmlReportDefaultCriteriaExporter $default_criteria_exporter,
        XML_SimpleXMLCDATAFactory $cdata_factory,
        XmlReportTableExporter $report_table_exporter,
        StatusValuesCollection $status_values_collection
    ) {
        $this->default_criteria_exporter = $default_criteria_exporter;
        $this->cdata_factory             = $cdata_factory;
        $this->report_table_exporter     = $report_table_exporter;
        $this->status_values_collection  = $status_values_collection;
    }

    public function exportJiraLikeReport(
        SimpleXMLElement $reports_node,
        ?FieldMapping $summary_field,
        ?FieldMapping $description_field,
        ?FieldMapping $status_field,
        ?FieldMapping $priority_field,
        ?FieldMapping $link_field,
        ?FieldMapping $created_field,
        ?FieldMapping $updated_field
    ): void {
        if (! $status_field) {
            return;
        }

        $done_values = $this->status_values_collection->getClosedValues();

        if (count($done_values) === 0) {
            return;
        }

        $report_node = $reports_node->addChild('report');
        $report_node->addAttribute("is_default", "0");

        $this->cdata_factory->insert($report_node, 'name', 'Done issues');
        $this->cdata_factory->insert($report_node, 'description', 'All done issues in this tracker');

        $criteria_fields = array_filter(
            [
               $summary_field,
               $description_field,
               $priority_field
            ]
        );

        $this->exportDoneIssuesCriteria(
            $report_node,
            $criteria_fields,
            $status_field,
            $done_values
        );

        $column_fields = array_filter(
            [
                $summary_field,
                $status_field,
                $link_field,
                $priority_field
            ]
        );

        $this->report_table_exporter->exportResultsTable(
            $report_node,
            $column_fields
        );
    }

    /**
     * @param FieldMapping[] $field_mappings
     * @param JiraFieldAPIAllowedValueRepresentation[] $done_values
     */
    private function exportDoneIssuesCriteria(
        SimpleXMLElement $report_node,
        array $field_mappings,
        FieldMapping $status_field,
        array $done_values
    ): void {
        $criterias_node = $report_node->addChild('criterias');
        $criteria_node  = $criterias_node->addChild('criteria');

        $criteria_node->addAttribute('rank', '0');
        $criteria_node->addAttribute('is_advanced', '1');

        $criteria_field_node = $criteria_node->addChild('field');

        $criteria_field_node->addAttribute("REF", $status_field->getXMLId());

        $criteria_value = $criteria_node->addChild('criteria_value');

        $criteria_value->addAttribute('type', 'list');

        foreach ($done_values as $done_value) {
            $selected_value = $criteria_value->addChild('selected_value');
            $selected_value->addAttribute('REF', "V" . $done_value->getId());
        }

        $this->default_criteria_exporter->exportDefaultCriteria($field_mappings, $criterias_node);
    }
}
