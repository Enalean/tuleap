<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Reports;

use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use XML_SimpleXMLCDATAFactory;

class XmlReportExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $cdata_factory;

    public function __construct(XML_SimpleXMLCDATAFactory $cdata_factory)
    {
        $this->cdata_factory = $cdata_factory;
    }

    public function exportReports(SimpleXMLElement $trackers_node, FieldMappingCollection $field_mapping_collection): void
    {
        $reports_node = $trackers_node->addChild('reports');
        $report_node  = $reports_node->addChild('report');

        $this->cdata_factory->insert($report_node, 'name', 'All issues');
        $this->cdata_factory->insert($report_node, 'description', 'All the issues in this tracker');

        $summary_field     = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_SUMMARY_FIELD_NAME);
        $description_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_DESCRIPTION_FIELD_NAME);
        $status_field      = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_STATUS_NAME);
        $priority_field    = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME);
        $link_field        = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME);

        $criterias_fields = array_filter([
            $summary_field,
            $description_field,
            $status_field,
            $priority_field
        ]);

        $this->exportCriterias(
            $report_node,
            $criterias_fields
        );

        $renderers_node = $report_node->addChild('renderers');
        $renderer_node  = $renderers_node->addChild('renderer');
        $renderer_node->addAttribute('rank', "0");
        $renderer_node->addAttribute('type', "table");
        $renderer_node->addAttribute('chunksz', "15");

        $this->cdata_factory->insert($renderer_node, 'name', 'Results');

        $column_fields = array_filter([
            $summary_field,
            $status_field,
            $link_field,
            $priority_field
        ]);

        $this->exportReportColumns(
            $renderer_node,
            $column_fields
        );
    }

    /**
     * @param FieldMapping[] $field_mappings
     */
    private function exportCriterias(
        SimpleXMLElement $report_node,
        array $field_mappings
    ): void {
        $criterias_node = $report_node->addChild('criterias');
        $rank_in_node = 0;
        foreach ($field_mappings as $field_mapping) {
            $criteria_node  = $criterias_node->addChild('criteria');
            $criteria_node->addAttribute("rank", (string) $rank_in_node);
            $criteria_field_node = $criteria_node->addChild("field");
            $criteria_field_node->addAttribute("REF", $field_mapping->getXMLId());
            $rank_in_node++;
        }
    }

    /**
     * @param FieldMapping[] $field_mappings
     */
    private function exportReportColumns(
        SimpleXMLElement $renderer_node,
        array $field_mappings
    ): void {
        $columns_node = $renderer_node->addChild('columns');
        foreach ($field_mappings as $field_mapping) {
            $field_node = $columns_node->addChild('field');
            $field_node->addAttribute('REF', $field_mapping->getXMLId());
        }
    }
}
