<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Semantic;

use SimpleXMLElement;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;

class SemanticsXMLExporter
{
    public function exportSemantics(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection
    ): void {
        $semantics_node = $tracker_node->addChild('semantics');
        $this->exportTitleSemantic($semantics_node, $field_mapping_collection);
        $this->exportDescriptionSemantic($semantics_node, $field_mapping_collection);
        $this->exportStatusSemantic($semantics_node, $field_mapping_collection, $status_values_collection);
    }

    private function exportTitleSemantic(SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $summary_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_SUMMARY_FIELD_NAME);
        if ($summary_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", Tracker_Semantic_Title::NAME);

        $semantic_node->addChild("shortname", Tracker_Semantic_Title::NAME);
        $semantic_node->addChild("label", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'title_label'));
        $semantic_node->addChild("description", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'title_description'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", (string) $summary_field->getXMLId());
    }

    private function exportDescriptionSemantic(SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $description_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_DESCRIPTION_FIELD_NAME);
        if ($description_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", Tracker_Semantic_Description::NAME);

        $semantic_node->addChild("shortname", Tracker_Semantic_Description::NAME);
        $semantic_node->addChild("label", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_label'));
        $semantic_node->addChild("description", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'description_description'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", (string) $description_field->getXMLId());
    }

    private function exportStatusSemantic(
        SimpleXMLElement $semantics_node,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection
    ): void {
        $status_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_STATUS_NAME);
        if ($status_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", Tracker_Semantic_Status::NAME);

        $semantic_node->addChild("shortname", Tracker_Semantic_Status::NAME);
        $semantic_node->addChild("label", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_label'));
        $semantic_node->addChild("description", $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'status_description'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", (string) $status_field->getXMLId());
        $open_values_node = $semantic_node->addChild("open_values");

        foreach ($status_values_collection->getOpenValues() as $allowed_value_representation) {
            $open_value_node = $open_values_node->addChild('open_value');
            $open_value_node->addAttribute("REF", "V" . $allowed_value_representation->getId());
        }
    }
}
