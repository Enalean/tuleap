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
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
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
     * @var IssueSnapshotCollectionBuilder
     */
    private $issue_snapshot_collection_builder;

    /**
     * @var LastDataChangesetXMLUpdater
     */
    private $last_data_changeset_xml_updater;

    public function __construct(
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        FieldChangeXMLExporter $field_change_xml_exporter,
        IssueSnapshotCollectionBuilder $issue_snapshot_collection_builder,
        LastDataChangesetXMLUpdater $last_data_changeset_xml_updater
    ) {
        $this->simplexml_cdata_factory           = $simplexml_cdata_factory;
        $this->field_change_xml_exporter         = $field_change_xml_exporter;
        $this->issue_snapshot_collection_builder = $issue_snapshot_collection_builder;
        $this->last_data_changeset_xml_updater   = $last_data_changeset_xml_updater;
    }

    public function exportIssueDataInChangesetXML(
        PFUser $user,
        SimpleXMLElement $artifact_node,
        FieldMappingCollection $jira_field_mapping_collection,
        array $issue,
        string $jira_base_url
    ): void {
        $snapshot_collection = $this->issue_snapshot_collection_builder->buildCollectionOfSnapshotsForIssue(
            $user,
            $issue,
            $jira_field_mapping_collection
        );

        $last_item_key = array_key_last($snapshot_collection);
        foreach ($snapshot_collection as $key => $snapshot) {
            $changeset_node = $artifact_node->addChild('changeset');
            $this->exportSnapshotInXML($snapshot, $changeset_node);

            if ($key === $last_item_key) {
                $this->last_data_changeset_xml_updater->updateLastXMLChangeset(
                    $issue,
                    $jira_base_url,
                    $changeset_node,
                    $jira_field_mapping_collection
                );
            }
        }
    }

    private function exportSnapshotInXML(Snapshot $snapshot, SimpleXMLElement $changeset_node): void
    {
        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_by',
            $snapshot->getUser()->getUserName(),
            $format = ['format' => 'username']
        );

        $this->simplexml_cdata_factory->insertWithAttributes(
            $changeset_node,
            'submitted_on',
            date('c', $snapshot->getDate()->getTimestamp()),
            $format = ['format' => 'ISO8601']
        );

        $changeset_node->addChild('comments');

        $this->field_change_xml_exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );
    }
}
