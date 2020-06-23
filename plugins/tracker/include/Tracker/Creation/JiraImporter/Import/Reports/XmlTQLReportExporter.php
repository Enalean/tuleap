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

class XmlTQLReportExporter
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
     * @param FieldMapping[] $column_fields
     */
    public function __construct(
        XmlReportDefaultCriteriaExporter $default_criteria_exporter,
        XML_SimpleXMLCDATAFactory $cdata_factory,
        XmlReportTableExporter $report_table_exporter
    ) {
        $this->default_criteria_exporter = $default_criteria_exporter;
        $this->cdata_factory             = $cdata_factory;
        $this->report_table_exporter     = $report_table_exporter;
    }

    /**
     * @param FieldMapping[] $criteria_fields
     * @param FieldMapping[] $column_fields
     */
    public function exportTQLReport(
        SimpleXMLElement $reports_node,
        string $report_name,
        string $report_description,
        bool $is_default,
        string $tql_query,
        array $criteria_fields,
        array $column_fields
    ): void {
        $report_node = $reports_node->addChild('report');
        $report_node->addAttribute("is_default", $is_default ? '1' : '0');
        $report_node->addAttribute("is_in_expert_mode", "1");
        $report_node->addAttribute("expert_query", $tql_query);

        $this->cdata_factory->insert($report_node, 'name', $report_name);
        $this->cdata_factory->insert($report_node, 'description', $report_description);

        $criterias_node = $report_node->addChild('criterias');

        $this->default_criteria_exporter->exportDefaultCriteria($criteria_fields, $criterias_node);
        $this->report_table_exporter->exportResultsTable(
            $report_node,
            $column_fields
        );
    }
}
