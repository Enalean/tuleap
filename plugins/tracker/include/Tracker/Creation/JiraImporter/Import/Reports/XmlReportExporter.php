<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

use Tuleap\Tracker\Creation\JiraImporter\Import\JiraXmlExporter;
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

    public function exportReports(\SimpleXMLElement $trackers_node, FieldMappingCollection $field_mapping_collection): void
    {
        $reports_node = $trackers_node->addChild('reports');
        $report_node  = $reports_node->addChild('report');

        $this->cdata_factory->insert($report_node, 'name', 'Default');
        $this->cdata_factory->insert($report_node, 'description', 'The system default artifact report');

        $report_node->addChild('criterias');

        $renderers_node = $report_node->addChild('renderers');
        $renderer_node  = $renderers_node->addChild('renderer');
        $renderer_node->addAttribute('rank', "0");
        $renderer_node->addAttribute('type', "table");
        $renderer_node->addAttribute('chunksz', "15");

        $this->cdata_factory->insert($renderer_node, 'name', 'Results');
        $columns_node = $renderer_node->addChild('columns');

        $summary_field = $field_mapping_collection->getMappingFromJiraField(JiraXmlExporter::JIRA_SUMMARY_FIELD_NAME);
        if ($summary_field === null) {
            return;
        }
        $field_node   = $columns_node->addChild('field');

        $field_node->addAttribute('REF', $summary_field->getXMLId());
    }
}
