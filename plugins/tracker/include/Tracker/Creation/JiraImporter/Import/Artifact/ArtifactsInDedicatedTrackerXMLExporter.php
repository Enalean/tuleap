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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use LogicException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Attachment\AttachmentXMLExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraCollectionBuilder;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;
use UserManager;

class ArtifactsInDedicatedTrackerXMLExporter
{
    /**
     * @var JiraClient
     */
    private $jira_client;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var DataChangesetXMLExporter
     */
    private $data_changeset_xml_exporter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AttachmentCollectionBuilder
     */
    private $attachment_collection_builder;
    /**
     * @var AttachmentXMLExporter
     */
    private $attachment_xml_exporter;

    public function __construct(
        JiraClient $jira_client,
        UserManager $user_manager,
        DataChangesetXMLExporter $data_changeset_xml_exporter,
        AttachmentCollectionBuilder $attachment_collection_builder,
        AttachmentXMLExporter $attachment_xml_exporter,
        LoggerInterface $logger,
    ) {
        $this->jira_client                   = $jira_client;
        $this->user_manager                  = $user_manager;
        $this->data_changeset_xml_exporter   = $data_changeset_xml_exporter;
        $this->attachment_collection_builder = $attachment_collection_builder;
        $this->attachment_xml_exporter       = $attachment_xml_exporter;
        $this->logger                        = $logger;
    }

    /**
     * @throws JiraConnectionException
     */
    public function exportArtifacts(
        SimpleXMLElement $tracker_node,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentationCollection $issue_representation_collection,
        LinkedIssuesCollection $linked_issues_collection,
        string $jira_base_url,
        string $jira_project_id,
        string $jira_issue_type_name,
    ): void {
        $user = $this->user_manager->getUserById(TrackerImporterUser::ID);
        if ($user === null) {
            return;
        }

        $artifacts_node = $tracker_node->addChild('artifacts');
        if ($artifacts_node === null) {
            throw new LogicException('must not be here.');
        }

        $already_seen_artifacts_ids = [];

        $iterator = JiraCollectionBuilder::iterateUntilTotal(
            $this->jira_client,
            $this->logger,
            $this->getUrl($jira_project_id, $jira_issue_type_name),
            'issues',
        );
        foreach ($iterator as $issue) {
            $this->exportBatchOfIssuesInArtifactXMLFormat(
                $artifacts_node,
                $issue,
                $jira_base_url,
                $jira_field_mapping_collection,
                $issue_representation_collection,
                $linked_issues_collection,
                $already_seen_artifacts_ids,
            );
        }
    }

    private function getUrl(
        string $jira_project_id,
        string $jira_issue_type_id,
    ): string {
        $params = [
            'jql'    => 'project="' . $jira_project_id . '" AND issuetype=' . $jira_issue_type_id,
            'fields' => '*all',
            'expand' => 'renderedFields',
        ];

        return ClientWrapper::JIRA_CORE_BASE_URL . '/search?' . http_build_query($params);
    }

    /**
     * @param array<int, true> $already_seen_artifacts_ids
     *
     * @throws JiraConnectionException
     */
    private function exportBatchOfIssuesInArtifactXMLFormat(
        SimpleXMLElement $artifacts_node,
        array $issue,
        string $jira_base_url,
        FieldMappingCollection $jira_field_mapping_collection,
        IssueAPIRepresentationCollection $issue_representation_collection,
        LinkedIssuesCollection $linked_issues_collection,
        array &$already_seen_artifacts_ids,
    ): void {
        $issue_api_representation = IssueAPIRepresentation::buildFromAPIResponse($issue);
        $issue_representation_collection->addIssueRepresentationInCollection($issue_api_representation);

        $issue_id  = $issue_api_representation->getId();
        $issue_key = $issue_api_representation->getKey();

        if (isset($already_seen_artifacts_ids[$issue_id])) {
            $this->logger->debug("$issue_key (id: $issue_id) has already be exported, no need to export it again");
            return;
        }
        $already_seen_artifacts_ids[$issue_id] = true;

        $this->logger->debug("Exporting issue $issue_key (id: $issue_id)");

        $artifact_node = $artifacts_node->addChild('artifact');
        if ($artifact_node === null) {
            throw new LogicException('must not be here.');
        }

        $artifact_node->addAttribute('id', (string) $issue_id);

        $attachment_collection = $this->attachment_collection_builder->buildCollectionOfAttachment(
            $issue_api_representation
        );

        $this->logger->debug("  |_ Exporting data for issue");
        $this->data_changeset_xml_exporter->exportIssueDataInChangesetXML(
            $artifact_node,
            $jira_field_mapping_collection,
            $issue_api_representation,
            $attachment_collection,
            $linked_issues_collection,
            $jira_base_url
        );

        //Export file info in XML
        $this->logger->debug("  |_ Exporting attachements for issue");
        $this->attachment_xml_exporter->exportCollectionOfAttachmentInXML(
            $attachment_collection,
            $artifact_node
        );
    }
}
