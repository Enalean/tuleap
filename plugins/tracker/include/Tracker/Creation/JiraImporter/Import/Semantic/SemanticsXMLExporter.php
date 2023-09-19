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

use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleXMLElement;
use Tracker_Semantic_Contributor;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesCollection;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById;
use Tuleap\Tracker\FormElement\XML\XMLFormElementFlattenedCollection;
use Tuleap\Tracker\Semantic\Status\Done\XML\XMLDoneSemantic;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;

class SemanticsXMLExporter
{
    public function __construct(private readonly EventDispatcherInterface $event_dispatcher)
    {
    }

    public function exportSemantics(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection,
    ): void {
        $semantics_node = $tracker_node->addChild('semantics');
        $this->exportTitleSemantic($semantics_node, $field_mapping_collection);
        $this->exportDescriptionSemantic($semantics_node, $field_mapping_collection);
        $this->exportStatusSemantic($semantics_node, $field_mapping_collection, $status_values_collection);
        $this->exportDoneSemantic($semantics_node, $field_mapping_collection, $status_values_collection);
        $this->exportContributorSemantic($semantics_node, $field_mapping_collection);
        $this->exportTimeframeSemantic($semantics_node, $field_mapping_collection);
        $this->exportExternalSemantics($semantics_node, $field_mapping_collection);
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
        $semantic_node->addChild("label", dgettext('tuleap-tracker', 'Title'));
        $semantic_node->addChild("description", dgettext('tuleap-tracker', 'Define the title of an artifact'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", $summary_field->getXMLId());
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
        $semantic_node->addChild("label", dgettext('tuleap-tracker', 'Description'));
        $semantic_node->addChild("description", dgettext('tuleap-tracker', 'Define the description of an artifact'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", $description_field->getXMLId());
    }

    private function exportStatusSemantic(
        SimpleXMLElement $semantics_node,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection,
    ): void {
        $status_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_STATUS_NAME);
        if ($status_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", Tracker_Semantic_Status::NAME);

        $semantic_node->addChild("shortname", Tracker_Semantic_Status::NAME);
        $semantic_node->addChild("label", dgettext('tuleap-tracker', 'Status'));
        $semantic_node->addChild("description", dgettext('tuleap-tracker', 'Define the status of an artifact'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", (string) $status_field->getXMLId());
        $open_values_node = $semantic_node->addChild("open_values");

        foreach ($status_values_collection->getOpenValues() as $allowed_value_representation) {
            $open_value_node = $open_values_node->addChild('open_value');
            $open_value_node->addAttribute("REF", $allowed_value_representation->getXMLId());
        }
    }

    private function exportDoneSemantic(
        SimpleXMLElement $semantics_node,
        FieldMappingCollection $field_mapping_collection,
        StatusValuesCollection $status_values_collection,
    ): void {
        $status_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_STATUS_NAME);
        if ($status_field === null) {
            return;
        }

        $xml_done = new XMLDoneSemantic();
        foreach ($status_values_collection->getClosedValues() as $closed_value) {
            $xml_done = $xml_done->withDoneValues(
                new XMLBindValueReferenceById($closed_value->getXMLId()),
            );
        }
        $xml_done->export($semantics_node, new XMLFormElementFlattenedCollection([]));
    }

    private function exportContributorSemantic(SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $assignee_field = $field_mapping_collection->getMappingFromJiraField(AlwaysThereFieldsExporter::JIRA_ASSIGNEE_NAME);
        if ($assignee_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", Tracker_Semantic_Contributor::CONTRIBUTOR_SEMANTIC_SHORTNAME);

        $semantic_node->addChild("shortname", Tracker_Semantic_Contributor::CONTRIBUTOR_SEMANTIC_SHORTNAME);
        $semantic_node->addChild("label", dgettext('tuleap-tracker', 'Contributor/assignee'));
        $semantic_node->addChild("description", dgettext('tuleap-tracker', 'Define the contributor/assignee of an artifact'));
        $field_node = $semantic_node->addChild("field");
        $field_node->addAttribute("REF", $assignee_field->getXMLId());
    }

    private function exportTimeframeSemantic(SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $start_date_field = null;
        foreach ($field_mapping_collection->getAllMappings() as $mapping) {
            if ($mapping->getJiraFieldLabel() === "Start date" && $mapping->getType() === "date") {
                $start_date_field = $mapping;
                break;
            }
        }

        if ($start_date_field === null) {
            return;
        }

        $due_date_field = $field_mapping_collection->getMappingFromJiraField("duedate");
        if ($due_date_field === null) {
            return;
        }

        $semantic_node = $semantics_node->addChild("semantic");
        $semantic_node->addAttribute("type", SemanticTimeframe::NAME);

        $semantic_node->addChild("start_date_field")->addAttribute("REF", $start_date_field->getXMLId());
        $semantic_node->addChild("end_date_field")->addAttribute("REF", $due_date_field->getXMLId());
    }

    private function exportExternalSemantics(SimpleXMLElement $semantics_node, FieldMappingCollection $field_mapping_collection): void
    {
        $this->event_dispatcher->dispatch(
            new ExternalSemanticsExportEvent($semantics_node, $field_mapping_collection)
        );
    }
}
