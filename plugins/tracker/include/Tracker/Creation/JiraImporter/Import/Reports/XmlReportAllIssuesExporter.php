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
use XML_SimpleXMLCDATAFactory;

class XmlReportAllIssuesExporter implements IExportJiraLikeXmlReport
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

    public function __construct(
        XmlReportDefaultCriteriaExporter $default_criteria_exporter,
        XML_SimpleXMLCDATAFactory $cdata_factory,
        XmlReportTableExporter $report_table_exporter
    ) {
        $this->default_criteria_exporter = $default_criteria_exporter;
        $this->cdata_factory             = $cdata_factory;
        $this->report_table_exporter     = $report_table_exporter;
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
        $report_node = $reports_node->addChild('report');
        $report_node->addAttribute("is_default", "1");

        $this->cdata_factory->insert($report_node, 'name', 'All issues');
        $this->cdata_factory->insert($report_node, 'description', 'All the issues in this tracker');

        $criteria_fields = array_filter(
            [
                $summary_field,
                $description_field,
                $status_field,
                $priority_field
            ]
        );

        $column_fields = array_filter([
            $summary_field,
            $status_field,
            $link_field,
            $priority_field
        ]);

        $this->default_criteria_exporter->exportDefaultCriteria(
            $criteria_fields,
            $report_node->addChild('criterias')
        );

        $this->report_table_exporter->exportResultsTable($report_node, $column_fields);
    }
}
