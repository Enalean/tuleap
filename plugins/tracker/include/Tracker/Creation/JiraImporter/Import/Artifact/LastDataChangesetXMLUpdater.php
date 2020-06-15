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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;

class LastDataChangesetXMLUpdater
{
    /**
     * @var FieldChangeStringBuilder
     */
    private $field_change_string_builder;

    /**
     * @var FieldChangeTextBuilder
     */
    private $field_change_text_builder;

    public function __construct(
        FieldChangeStringBuilder $field_change_string_builder,
        FieldChangeTextBuilder $field_change_text_builder
    ) {
        $this->field_change_string_builder = $field_change_string_builder;
        $this->field_change_text_builder   = $field_change_text_builder;
    }

    public function updateLastXMLChangeset(
        array $issue,
        string $jira_base_url,
        SimpleXMLElement $changeset_node,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        $this->addTuleapRelatedInformationOnLastXMLSnapshot($issue, $jira_base_url, $changeset_node);
        $this->updateTextFieldsWithHTMLFormat($issue, $changeset_node, $jira_field_mapping_collection);
    }

    private function addTuleapRelatedInformationOnLastXMLSnapshot(
        array $issue,
        string $jira_base_url,
        SimpleXMLElement $changeset_node
    ): void {
        $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($issue['key']);
        $this->field_change_string_builder->build(
            $changeset_node,
            AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME,
            $jira_link
        );
    }

    private function updateTextFieldsWithHTMLFormat(
        array $issue,
        SimpleXMLElement $changeset_node,
        FieldMappingCollection $jira_field_mapping_collection
    ): void {
        foreach ($issue['renderedFields'] as $field_name => $rendered_value) {
            if ($rendered_value === null || ! is_string($rendered_value)) {
                continue;
            }

            $mapping = $jira_field_mapping_collection->getMappingFromJiraField($field_name);
            if ($mapping === null) {
                continue;
            }

            if ($mapping->getType() !== Tracker_FormElementFactory::FIELD_TEXT_TYPE) {
                continue;
            }

            $this->removeNodeInXMLContent($changeset_node, $field_name);

            $this->field_change_text_builder->build(
                $changeset_node,
                $field_name,
                $rendered_value,
                Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
            );
        }
    }

    private function removeNodeInXMLContent(SimpleXMLElement $changeset_node, string $field_name): void
    {
        $index = 0;
        foreach ($changeset_node->field_change as $xml_field_change) {
            if ((string) $xml_field_change['field_name'] === $field_name) {
                unset($changeset_node->field_change[$index]);
                return;
            }
            $index++;
        }
    }
}
