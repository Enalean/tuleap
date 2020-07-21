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

use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment\CommentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\IssueSnapshotCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
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
     * @var CommentXMLExporter
     */
    private $comment_xml_exporter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        XML_SimpleXMLCDATAFactory $simplexml_cdata_factory,
        FieldChangeXMLExporter $field_change_xml_exporter,
        IssueSnapshotCollectionBuilder $issue_snapshot_collection_builder,
        CommentXMLExporter $comment_xml_exporter,
        LoggerInterface $logger
    ) {
        $this->simplexml_cdata_factory           = $simplexml_cdata_factory;
        $this->field_change_xml_exporter         = $field_change_xml_exporter;
        $this->issue_snapshot_collection_builder = $issue_snapshot_collection_builder;
        $this->comment_xml_exporter              = $comment_xml_exporter;
        $this->logger                            = $logger;
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportIssueDataInChangesetXML(
        SimpleXMLElement $artifact_node,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentation $issue_api_representation,
        AttachmentCollection $attachment_collection,
        string $jira_base_url
    ): void {
        $this->logger->debug("Start exporting data in changeset XML...");
        $snapshot_collection = $this->issue_snapshot_collection_builder->buildCollectionOfSnapshotsForIssue(
            $issue_api_representation,
            $attachment_collection,
            $jira_field_mapping_collection,
            $jira_base_url
        );

        foreach ($snapshot_collection as $key => $snapshot) {
            $changeset_node = $artifact_node->addChild('changeset');
            $this->exportSnapshotInXML($snapshot, $changeset_node);
        }

        $this->logger->debug("End exporting data in changeset XML...");
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

        $this->comment_xml_exporter->exportComment(
            $snapshot,
            $changeset_node
        );

        $this->field_change_xml_exporter->exportFieldChanges(
            $snapshot,
            $changeset_node
        );
    }
}
