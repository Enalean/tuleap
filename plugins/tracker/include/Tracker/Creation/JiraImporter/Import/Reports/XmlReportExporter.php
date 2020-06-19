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
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class XmlReportExporter
{
    public function exportReports(
        SimpleXMLElement $trackers_node,
        FieldMappingCollection $field_mapping_collection,
        XmlReportAllIssuesExporter $xml_report_all_issues_exporter,
        XmlReportOpenIssuesExporter $xml_report_open_issues_exporter
    ): void {
        $reports_node = $trackers_node->addChild('reports');

        $summary_field     = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_SUMMARY_FIELD_NAME);
        $description_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_DESCRIPTION_FIELD_NAME);
        $status_field      = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_STATUS_NAME);
        $priority_field    = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME);
        $link_field        = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME);



        $xml_report_all_issues_exporter->exportJiraLikeReport(
            $reports_node,
            $summary_field,
            $description_field,
            $status_field,
            $priority_field,
            $link_field
        );

        $xml_report_open_issues_exporter->exportJiraLikeReport(
            $reports_node,
            $summary_field,
            $description_field,
            $status_field,
            $priority_field,
            $link_field
        );
    }
}
