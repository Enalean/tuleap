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

use PFUser;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot\InitialSnapshotDataGenerator;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use XML_SimpleXMLCDATAFactory;

class DataChangesetXMLExporter
{
    /**
     * @var XML_SimpleXMLCDATAFactory
     */
    private $simplexml_cdata_factory;

    /**
     * @var FieldChangeXMLExporter
     */
    private $field_change_xml_exporter;

    /**
     * @var FieldChangeStringBuilder
     */
    private $field_change_string_builder;

    /**
     * @var InitialSnapshotDataGenerator
     */
    private $initial_snapshot_data_generator;

    public function __construct(
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        FieldChangeXMLExporter $field_change_xml_exporter,
        FieldChangeStringBuilder $field_change_string_builder,
        InitialSnapshotDataGenerator $initial_snapshot_data_generator
    ) {
        $this->simplexml_cdata_factory         = $simplexml_cdata_factory;
        $this->field_change_xml_exporter       = $field_change_xml_exporter;
        $this->field_change_string_builder     = $field_change_string_builder;
        $this->initial_snapshot_data_generator = $initial_snapshot_data_generator;
    }

    public function exportIssueDataInChangesetXML(
        PFUser $user,
        SimpleXMLElement $artifact_node,
        FieldMappingCollection $jira_field_mapping_collection,
        array $issue,
        string $jira_base_url
    ): void {
        $current_snapshot = $this->buildCurrentSnapshot($issue, $jira_field_mapping_collection);

        $this->importInitialChangeset(
            $user,
            $artifact_node,
            $issue,
            $current_snapshot
        );

        $this->importCurrentStateChangeset(
            $user,
            $artifact_node,
            $issue,
            $current_snapshot,
            $jira_base_url
        );
    }

    private function importCurrentStateChangeset(
        PFUser $user,
        SimpleXMLElement $artifact_node,
        array $issue,
        Snapshot $current_snapshot,
        string $jira_base_url
    ): void {
        $changeset_node = $artifact_node->addChild('changeset');

        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_by',
            $user->getUserName(),
            $format = ['format' => 'username']
        );

        $updated_date = $issue['fields'][AlwaysThereFieldsExporter::JIRA_UPDATED_ON_NAME];
        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_on',
            $updated_date,
            $format = ['format' => 'ISO8601']
        );

        $changeset_node->addChild('comments');

        $jira_link = rtrim($jira_base_url, "/") . "/browse/" . urlencode($issue['key']);
        $this->field_change_string_builder->build(
            $changeset_node,
            AlwaysThereFieldsExporter::JIRA_LINK_FIELD_NAME,
            $jira_link
        );

        $this->field_change_xml_exporter->exportFieldChanges(
            $current_snapshot,
            $changeset_node,
        );
    }

    private function importInitialChangeset(
        PFUser $user,
        SimpleXMLElement $artifact_node,
        array $issue,
        Snapshot $current_snapshot
    ): void {
        $changeset_node = $artifact_node->addChild('changeset');

        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_by',
            $user->getUserName(),
            $format = ['format' => 'username']
        );

        $creation_date = $issue['fields'][AlwaysThereFieldsExporter::JIRA_CREATED_NAME];
        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_on',
            $creation_date,
            $format = ['format' => 'ISO8601']
        );

        $changeset_node->addChild('comments');

        $initial_snapshot = $this->initial_snapshot_data_generator->generateInitialSnapshotContent($current_snapshot, $issue['key']);
        $this->field_change_xml_exporter->exportFieldChanges(
            $initial_snapshot,
            $changeset_node
        );
    }

    private function buildCurrentSnapshot(array $issue, FieldMappingCollection $jira_field_mapping_collection): Snapshot
    {
        $current_snapshot = new Changelog\Snapshot\Snapshot();
        foreach ($issue['fields'] as $key => $value) {
            $rendered_value = $issue['renderedFields'][$key] ?? null;
            $mapping        = $jira_field_mapping_collection->getMappingFromJiraField($key);
            if ($mapping !== null && $value !== null) {
                $current_snapshot->addFieldSnapshot(
                    new Changelog\Snapshot\FieldSnapshot(
                        $mapping,
                        $value,
                        $rendered_value
                    )
                );
            }
        }

        return $current_snapshot;
    }
}
